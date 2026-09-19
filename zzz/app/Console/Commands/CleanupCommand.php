<?php

namespace App\Console\Commands;

use App\Models\ServiceAdDraft;
use App\Models\ServiceSubscription;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/*
|--------------------------------------------------------------------------
| پاک‌سازی دوره‌ای
|--------------------------------------------------------------------------
|
| چهار چیز در این سیستم به‌مرور تلنبار می‌شوند و هیچ‌کس پاکشان
| نمی‌کرد. در دیتابیسِ فعلی هر چهار مورد قابل مشاهده‌اند:
|
|  ۱. فایل تصاویر بی‌صاحب - در storage/ads حدود ۴۴ فایل هست در حالی
|     که جدول ad_images فقط ۲ ردیف دارد. اینها از آگهی‌هایی مانده‌اند
|     که ردیفشان از دیتابیس حذف شده ولی فایلشان روی دیسک جا مانده.
|
|  ۲. اشتراک‌های «pending» رهاشده - کاربر به درگاه می‌رود و برنمی‌گردد.
|     در دیتابیس فعلی ۲۵ ردیف اشتراک هست که اکثرشان همین‌اند.
|
|  ۳. کدهای یک‌بارمصرف مصرف‌شده/منقضی - ۴۷ ردیف در login_otps.
|
|  ۴. پیش‌نویس‌های منقضی‌شده‌ی آگهی و فایل‌هایشان.
|
| این دستور عمداً محافظه‌کار است: فایلی را حذف می‌کند که هم در
| دیتابیس مرجعی ندارد و هم دست‌کم ۲۴ ساعت از ساخته‌شدنش گذشته باشد
| (تا آپلودِ در جریانِ همین لحظه قربانی نشود).
|
| با --dry-run فقط گزارش می‌دهد و چیزی حذف نمی‌کند.
|
*/
class CleanupCommand extends Command
{
    protected $signature = 'sazmat:cleanup {--dry-run : فقط گزارش بده، چیزی حذف نکن}';

    protected $description = 'پاک‌سازی فایل‌های بی‌صاحب، پرداخت‌های ناتمام، OTPهای مصرف‌شده و پیش‌نویس‌های منقضی';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');

        if ($dry) {
            $this->warn('حالت آزمایشی: هیچ چیزی حذف نمی‌شود.');
        }

        $this->cleanOrphanImages($dry);
        $this->cleanAbandonedSubscriptions($dry);
        $this->cleanOtps($dry);
        $this->cleanExpiredDrafts($dry);

        $this->info('پاک‌سازی به پایان رسید.');

        return self::SUCCESS;
    }

    /*
    |--------------------------------------------------------------------------
    | ۱) فایل‌های تصویرِ بدون مرجع در دیتابیس
    |--------------------------------------------------------------------------
    */
    private function cleanOrphanImages(bool $dry): void
    {
        $disk = Storage::disk('public');

        if (! $disk->exists('ads')) {
            $this->line('پوشه‌ی ads وجود ندارد؛ رد شد.');
            return;
        }

        /*
        | مسیرهایی که واقعاً در دیتابیس استفاده می‌شوند. هم ad_images و
        | هم image_paths پیش‌نویس‌ها باید در نظر گرفته شوند، وگرنه
        | تصویرِ یک پیش‌نویسِ هنوز معتبر حذف می‌شود.
        */
        $used = DB::table('ad_images')->pluck('path')->all();

        foreach (ServiceAdDraft::whereNotNull('image_paths')->pluck('image_paths') as $json) {
            foreach ((array) json_decode((string) $json, true) as $path) {
                if (is_string($path)) {
                    $used[] = $path;
                }
            }
        }

        $used = array_flip($used);

        $cutoff = now()->subDay()->getTimestamp();

        $deleted = 0;
        $bytes = 0;

        foreach ($disk->files('ads') as $file) {

            if (isset($used[$file])) {
                continue;
            }

            // آپلودِ همین الان را حذف نکن.
            if ($disk->lastModified($file) > $cutoff) {
                continue;
            }

            $bytes += $disk->size($file);
            $deleted++;

            if (! $dry) {
                $disk->delete($file);
            }
        }

        $this->line(sprintf(
            'تصاویر بی‌صاحب: %d فایل (%s مگابایت)',
            $deleted,
            number_format($bytes / 1048576, 2)
        ));
    }

    /*
    |--------------------------------------------------------------------------
    | ۲) اشتراک‌هایی که کاربر شروع کرد ولی پرداخت نشد
    |--------------------------------------------------------------------------
    |
    | فقط ردیف‌هایی حذف می‌شوند که هم pending باشند، هم paid_at نداشته
    | باشند و هم بیش از ۲۴ ساعت از ساختشان گذشته باشد - یعنی قطعاً
    | دیگر هیچ callbackی برایشان نمی‌آید.
    |
    */
    private function cleanAbandonedSubscriptions(bool $dry): void
    {
        $query = ServiceSubscription::query()
            ->where('status', 'pending')
            ->whereNull('paid_at')
            ->where('created_at', '<=', now()->subDay());

        $count = $query->count();

        if (! $dry && $count > 0) {
            $query->delete();
        }

        $this->line("اشتراک‌های پرداخت‌نشده‌ی رهاشده: {$count} ردیف");
    }

    /*
    |--------------------------------------------------------------------------
    | ۳) کدهای یک‌بارمصرف
    |--------------------------------------------------------------------------
    |
    | کد بعد از ۳ دقیقه منقضی می‌شود؛ نگه‌داشتن ردیفش بعد از آن هیچ
    | فایده‌ای ندارد و فقط شماره موبایل کاربران را بی‌دلیل انبار
    | می‌کند. یک روز فرصت برای بررسی/لاگ کافی است.
    |
    */
    private function cleanOtps(bool $dry): void
    {
        $query = DB::table('login_otps')->where('expires_at', '<=', now()->subDay());

        $count = $query->count();

        if (! $dry && $count > 0) {
            $query->delete();
        }

        $this->line("کدهای یک‌بارمصرف منقضی: {$count} ردیف");
    }

    /*
    |--------------------------------------------------------------------------
    | ۴) پیش‌نویس‌های منقضی و فایل‌هایشان
    |--------------------------------------------------------------------------
    */
    private function cleanExpiredDrafts(bool $dry): void
    {
        $drafts = ServiceAdDraft::where('expires_at', '<=', now())->get();

        foreach ($drafts as $draft) {

            foreach ((array) json_decode((string) $draft->image_paths, true) as $path) {
                if (is_string($path) && ! $dry) {
                    Storage::disk('public')->delete($path);
                }
            }

            if (! $dry) {
                $draft->delete();
            }
        }

        $this->line('پیش‌نویس‌های منقضی: ' . $drafts->count() . ' ردیف');
    }
}
