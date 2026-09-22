<?php

namespace App\Console\Commands;

use App\Models\AdEdit;
use App\Models\ServiceAdDraft;
use App\Models\ServiceSubscription;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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

        $summary = [
            'تصاویر بی‌صاحب' => $this->cleanOrphanImages($dry),
            'اشتراک رهاشده' => $this->cleanAbandonedSubscriptions($dry),
            'کد یک‌بارمصرف' => $this->cleanOtps($dry),
            'پیش‌نویس منقضی' => $this->cleanExpiredDrafts($dry),
            'آزمایشی' => $dry,
        ];

        /*
        |--------------------------------------------------------------------------
        | یک خط در laravel.log
        |--------------------------------------------------------------------------
        |
        | ⚠️ خروجی این دستور در کرون به /dev/null می‌رود، پس تا امروز
        | هیچ‌جا معلوم نبود چه کرده - و وقتی زمان‌بند اصلاً صدایش
        | نمی‌زد هم دقیقاً همین‌قدر سکوت بود. یعنی «کار می‌کند» و
        | «هرگز اجرا نشده» از بیرون یک شکل داشتند.
        |
        | یک خط لاگ این دو را از هم جدا می‌کند: اگر در cron-tasks.log
        | نباشد، یعنی اجرا نشده.
        |
        | ⚠️ کانال cron، نه کانال پیش‌فرض: سطحش ثابت است و به
        | LOG_LEVEL کاری ندارد. با LOG_LEVEL=error - که در تولید
        | معمول است - این خط بی‌صدا دور ریخته می‌شد.
        */
        Log::channel('cron')->info('sazmat:cleanup', $summary);

        $this->info('پاک‌سازی به پایان رسید.');

        return self::SUCCESS;
    }

    /*
    |--------------------------------------------------------------------------
    | ۱) فایل‌های تصویرِ بدون مرجع در دیتابیس
    |--------------------------------------------------------------------------
    */
    private function cleanOrphanImages(bool $dry): int
    {
        $disk = Storage::disk('public');

        if (! $disk->exists('ads')) {
            $this->line('پوشه‌ی ads وجود ندارد؛ رد شد.');

            return 0;
        }

        /*
        | مسیرهایی که واقعاً در دیتابیس استفاده می‌شوند. هر سه منبع
        | باید در نظر گرفته شوند، وگرنه فایلی که هنوز به آن نیاز هست
        | حذف می‌شود.
        */
        $used = DB::table('ad_images')->pluck('path')->all();

        foreach (ServiceAdDraft::whereNotNull('image_paths')->pluck('image_paths') as $json) {
            foreach ((array) json_decode((string) $json, true) as $path) {
                if (is_string($path)) {
                    $used[] = $path;
                }
            }
        }

        /*
        |----------------------------------------------------------------------
        | تصاویرِ درخواست‌های ویرایشِ در انتظار بررسی
        |----------------------------------------------------------------------
        |
        | وقتی ارائه‌دهنده در فرم ویرایش تصویر تازه‌ای می‌فرستد، فایل
        | بلافاصله روی دیسک می‌نشیند ولی تا تأیید مدیر هیچ ردیفی در
        | ad_images نمی‌گیرد؛ تنها مرجعش ستون added_images در جدول
        | ad_edits است.
        |
        | این منبع اینجا جا افتاده بود. نتیجه‌اش یک باگ واقعی بود: اگر
        | مدیر درخواست را تا ۲۴ ساعت بررسی نمی‌کرد، همین پاک‌سازی شبانه
        | فایل‌ها را «بی‌صاحب» تشخیص می‌داد و حذف می‌کرد. بعد مدیر
        | تأیید می‌کرد، ردیف‌های ad_images ساخته می‌شدند و به فایل‌هایی
        | اشاره می‌کردند که دیگر وجود نداشتند - یعنی آگهی با تصویرهای
        | شکسته.
        |
        | فقط درخواست‌های pending شمرده می‌شوند و همین کافی است:
        | درخواست تأییدشده فایل‌هایش را به ad_images منتقل کرده (بالا
        | پوشش داده شد) و درخواست ردشده فایل‌هایش را همان لحظه حذف
        | کرده است.
        */
        foreach (AdEdit::pending()->whereNotNull('added_images')->pluck('added_images') as $paths) {
            foreach ((array) (is_string($paths) ? json_decode($paths, true) : $paths) as $path) {
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

        return $deleted;
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
    private function cleanAbandonedSubscriptions(bool $dry): int
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

        return $count;
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
    private function cleanOtps(bool $dry): int
    {
        $query = DB::table('login_otps')->where('expires_at', '<=', now()->subDay());

        $count = $query->count();

        if (! $dry && $count > 0) {
            $query->delete();
        }

        $this->line("کدهای یک‌بارمصرف منقضی: {$count} ردیف");

        return $count;
    }

    /*
    |--------------------------------------------------------------------------
    | ۴) پیش‌نویس‌های منقضی و فایل‌هایشان
    |--------------------------------------------------------------------------
    */
    private function cleanExpiredDrafts(bool $dry): int
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

        return $drafts->count();
    }
}
