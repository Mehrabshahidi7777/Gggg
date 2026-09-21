<?php

namespace App\Console\Commands;

use App\Models\ServiceSubscription;
use App\Services\AmootSmsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/*
|--------------------------------------------------------------------------
| یادآوری تمدید اشتراک
|--------------------------------------------------------------------------
|
| تا پیش از این، اشتراک بی‌صدا تمام می‌شد، زمان‌بند آگهی‌های کاربر را
| تعلیق می‌کرد و ارائه‌دهنده هفته‌ها بعد - معمولاً وقتی از یک مشتری
| می‌شنید که آگهی‌اش پیدا نمی‌شود - متوجه می‌شد. این هم برای او بد
| بود و هم مستقیماً درآمد شما را از بین می‌برد.
|
| حالا سه مرحله پیامک ارسال می‌شود:
|
|   ۷ روز مانده  → فرصت تمدید بدون وقفه
|   ۱ روز مانده  → آخرین هشدار
|   روز انقضا    → اطلاع تعلیق آگهی‌ها + مهلت شش‌ماهه
|
| هر مرحله دقیقاً یک بار ارسال می‌شود (ستون‌های reminder_*_sent_at).
|
*/
class SendSubscriptionRemindersCommand extends Command
{
    protected $signature = 'sazmat:subscription-reminders
                            {--dry-run : فقط گزارش بده، پیامکی ارسال نکن}';

    protected $description = 'ارسال پیامک یادآوری تمدید اشتراک (۷ روز مانده، ۱ روز مانده، روز انقضا)';

    public function handle(AmootSmsService $sms): int
    {
        $dry = (bool) $this->option('dry-run');

        if ($dry) {
            $this->warn('حالت آزمایشی: هیچ پیامکی ارسال نمی‌شود.');
        }

        $sent = 0;
        $sent += $this->stage($sms, $dry, 'reminder_7d_sent_at', 7);
        $sent += $this->stage($sms, $dry, 'reminder_1d_sent_at', 1);
        $sent += $this->stage($sms, $dry, 'reminder_expired_sent_at', 0);

        $this->info("مجموع پیامک‌های ارسال‌شده: {$sent}");

        return self::SUCCESS;
    }

    /*
    |--------------------------------------------------------------------------
    | یک مرحله‌ی یادآوری
    |--------------------------------------------------------------------------
    |
    | $daysLeft = 0 یعنی مرحله‌ی «منقضی شد».
    |
    */
    private function stage(AmootSmsService $sms, bool $dry, string $column, int $daysLeft): int
    {
        $now = now();

        $query = ServiceSubscription::query()
            ->with(['user', 'plan'])
            ->whereNull($column)
            ->whereNotNull('ends_at');

        if ($daysLeft > 0) {
            /*
            | اشتراکی که هنوز تمام نشده ولی تا حداکثر $daysLeft روز
            | دیگر تمام می‌شود.
            |
            | ⚠️ کفِ بازه هم لازم است، نه فقط سقفش.
            |
            | بدون آن، اشتراکی که ۱۲ ساعت دیگر تمام می‌شود در *همین
            | اجرا* هم در بازه‌ی ۷ روز می‌افتد و هم در بازه‌ی ۱ روز -
            | یعنی کاربر دو پیامک پشت سر هم می‌گیرد که دو چیز متفاوت
            | می‌گویند، و هزینه‌اش هم دو برابر است.
            |
            | پس هر مرحله فقط بازه‌ی خودش را برمی‌دارد: ۷ روز یعنی
            | «بین ۱ تا ۷ روز مانده».
            */
            $query->where('status', 'active')
                ->where('ends_at', '>', $now->copy()->addDays($this->nextStage($daysLeft)))
                ->where('ends_at', '<=', $now->copy()->addDays($daysLeft));
        } else {
            $query->where('ends_at', '<=', $now)
                ->whereIn('status', ['active', 'expired']);
        }

        $count = 0;

        $query->chunkById(100, function ($subscriptions) use ($sms, $dry, $column, $daysLeft, $now, &$count) {

            foreach ($subscriptions as $subscription) {

                /*
                | اگر کاربر از قبل تمدید کرده، این رکورد فقط یک حلقه از
                | زنجیره است و پایانش پایانِ واقعی اشتراک نیست. در این
                | حالت یادآوری بی‌معنی و گیج‌کننده است، پس ستون را
                | علامت می‌زنیم تا دیگر بررسی نشود و رد می‌شویم.
                */
                if ($this->hasLaterSubscription($subscription)) {
                    $subscription->forceFill([$column => now()])->save();
                    continue;
                }

                $mobile = $this->reachableMobile($subscription);

                /*
                | هیچ شماره‌ای پیدا نشد.
                |
                | ⚠️ این حالت بی‌صدا نمی‌ماند.
                |
                | ارائه‌دهنده‌ای که با ایمیل ثبت‌نام کرده شماره‌ی موبایل
                | ندارد و در پروفایل هم جایی برای افزودنش نیست. اگر
                | فقط رد می‌شدیم، اشتراکش بی‌خبر تمام می‌شد، آگهی‌هایش
                | تعلیق می‌شد، و هیچ‌کس - نه او، نه شما - نمی‌فهمید
                | چرا. پس در لاگ می‌نشیند تا در cron.log دیده شود.
                */
                if (! $mobile) {

                    Log::warning('subscription reminder skipped: no mobile', [
                        'subscription_id' => $subscription->id,
                        'user_id' => $subscription->user_id,
                        'stage' => $column,
                    ]);

                    $this->warn(
                        "  ⚠️ اشتراک {$subscription->id}: هیچ شماره‌ای برای تماس نیست."
                    );

                    $subscription->forceFill([$column => now()])->save();
                    continue;
                }

                $typeLabel = $subscription->type === 'product' ? 'محصولات' : 'خدمات';

                /*
                | وضعیت، به شکل یک عبارت کامل.
                |
                | ⚠️ این همان چیزی است که یک پترن را برای هر سه مرحله
                | کافی می‌کند. اگر به‌جایش عدد روز می‌رفت، مرحله‌ی
                | آخر پیامکِ «تا ۰ روز دیگر» می‌داد و ناچار بودیم
                | پترن دومی بسازیم.
                |
                | «فردا» هم از «تا ۱ روز دیگر» فارسی‌تر است.
                |
                | ⚠️ طول‌ها شمرده شده‌اند: بلندترین این عبارت‌ها پیام
                | را به ۶۵ کاراکتر می‌رساند، یعنی یک صفحه‌ی پیامک
                | فارسی (سقف ۷۰). هر کلمه‌ای که اینجا اضافه شود،
                | هزینه‌ی *هر* یادآوری را دو برابر می‌کند.
                */
                /*
                | ⚠️ عددِ واقعی، نه عددِ مرحله.
                |
                | $daysLeft سقفِ بازه‌ی این مرحله است. اشتراکی که سه
                | روز دیگر تمام می‌شود در مرحله‌ی «۷ روز» می‌افتد، و
                | اگر همان ۷ را می‌گفتیم پیامک دروغ می‌شد.
                */
                $remaining = (int) ceil($now->diffInDays($subscription->ends_at, false));

                $state = match (true) {
                    $daysLeft === 0 => 'تمام شد؛ آگهی‌ها تعلیق شدند',
                    $remaining <= 1 => 'فردا تمام می‌شود',
                    default => sprintf('تا %d روز دیگر تمام می‌شود', $remaining),
                };

                /*
                | متن پشتیبان، برای وقتی که پترنی ساخته نشده باشد.
                | ارسال آزاد سقف پترن را ندارد، پس اینجا مهلت شش‌ماهه
                | هم گفته می‌شود.
                */
                $message = $daysLeft > 0
                    ? sprintf(
                        'سازمت | اشتراک %s شما %s. برای جلوگیری از تعلیق آگهی‌ها آن را تمدید کنید. sazmat.com',
                        $typeLabel,
                        $state
                    )
                    : sprintf(
                        'سازمت | اشتراک %s شما به پایان رسید و آگهی‌هایتان تعلیق شد. تا شش ماه فرصت دارید با تمدید، آنها را بازگردانید. sazmat.com',
                        $typeLabel
                    );

                if ($dry) {
                    $this->line("  [آزمایشی] {$mobile} ← {$message}");
                    $count++;
                    continue;
                }

                try {

                    /*
                    | یک پترن برای هر سه مرحله. تفاوتِ مرحله‌ها در
                    | $state است، نه در پترن.
                    */
                    $sms->sendRenewalReminder($mobile, $message, [
                        $typeLabel,
                        $state,
                    ]);

                    $subscription->forceFill([$column => now()])->save();
                    $count++;

                } catch (\Throwable $e) {

                    /*
                    | ستون را علامت نمی‌زنیم تا اجرای فردا دوباره تلاش
                    | کند. یک پیامک ناموفق نباید بقیه‌ی صف را متوقف کند.
                    */
                    Log::warning('subscription reminder sms failed', [
                        'subscription_id' => $subscription->id,
                        'stage' => $column,
                        'exception' => $e->getMessage(),
                    ]);
                }
            }
        });

        $label = $daysLeft > 0 ? "{$daysLeft} روز مانده" : 'منقضی‌شده';
        $this->line("یادآوری {$label}: {$count} مورد");

        return $count;
    }

    /*
    |--------------------------------------------------------------------------
    | شماره‌ای که واقعاً می‌شود به آن پیامک زد
    |--------------------------------------------------------------------------
    |
    | ⚠️ هر ارائه‌دهنده‌ای موبایل ندارد.
    |
    | ثبت‌نام دو راه دارد: ایمیل یا موبایل. راهِ ایمیل اصلاً شماره
    | نمی‌گیرد، و پروفایل هم فقط نام کاربری را عوض می‌کند - یعنی چنین
    | کاربری هیچ‌وقت شماره پیدا نمی‌کند، ولی می‌تواند اشتراک بخرد.
    |
    | این وصله برای حساب‌های قدیمی است: از امروز، ثبت آگهی بدون
    | شماره‌ی تأییدشده ممکن نیست (RequireMobile)، ولی کسانی که پیش
    | از آن آگهی گذاشته‌اند همچنان شماره ندارند.
    |
    | پس اگر users.mobile خالی بود، سراغ شماره‌ی خودِ آگهی‌ها می‌رویم:
    | همان که ارائه‌دهنده برای تماس مشتری‌ها نوشته.
    |
    | ⚠️ فقط وقتی *همه‌ی* آگهی‌هایش به یک شماره برسند.
    |
    | این شماره را خودِ کاربر تأیید نکرده و ما فقط حدس می‌زنیم مالِ
    | اوست. اگر دو آگهی دو شماره‌ی متفاوت داشته باشند، معلوم نیست
    | کدام دست اوست - شاید یکی شماره‌ی شریک یا کارگاه باشد. حدسِ
    | غلط یعنی پیامکِ «اشتراکت تمام شد» به موبایل یک آدم بی‌خبر.
    |
    | مقایسه بعد از normalize_mobile انجام می‌شود، پس ۰۹۱۲…، ۹۸۹۱۲+
    | و ۰۰۹۸۹۱۲… یک شماره حساب می‌شوند - نه سه تا.
    |
    | تلفن ثابت از قلم می‌افتد؛ پیامک نمی‌گیرد و پذیرفتنش یعنی یک
    | «ارسال شد»ِ دروغ.
    |
    */
    private function reachableMobile(ServiceSubscription $subscription): ?string
    {
        $user = $subscription->user;

        if (! $user) {
            return null;
        }

        /* شماره‌ی خودِ حساب، که تأییدشده است، همیشه مقدم است. */
        if ($mobile = $this->asMobile($user->mobile)) {
            return $mobile;
        }

        $numbers = $user->ads()
            ->whereNotNull('phone')
            ->pluck('phone')
            ->map(fn ($phone) => $this->asMobile($phone))
            ->filter()
            ->unique()
            ->values();

        /* صفر یعنی هیچ موبایلی نبود؛ بیشتر از یکی یعنی معلوم نیست کدام. */
        return $numbers->count() === 1 ? $numbers->first() : null;
    }

    /* موبایل ایران: ۱۱ رقم، با ۰۹. هر چیز دیگر، null. */
    private function asMobile(?string $value): ?string
    {
        $digits = normalize_mobile($value);

        return $digits && strlen($digits) === 11 && str_starts_with($digits, '09')
            ? $digits
            : null;
    }

    /*
    | مرحله‌ی کوتاه‌ترِ بعدی، که کفِ بازه‌ی این مرحله است.
    |
    | ۷ → ۱ (مرحله‌ی بعدی «۱ روز مانده» است)
    | ۱ → ۰ (بعدش «منقضی شد»)
    |
    | اگر روزی مرحله‌ی تازه‌ای اضافه شد (مثلاً ۳ روز)، فقط همین آرایه
    | و handle() عوض می‌شوند.
    */
    private function nextStage(int $daysLeft): int
    {
        $stages = [7, 1, 0];

        foreach ($stages as $stage) {
            if ($stage < $daysLeft) {
                return $stage;
            }
        }

        return 0;
    }

    /*
    | آیا کاربر اشتراک دیگری از همین نوع دارد که دیرتر تمام می‌شود؟
    */
    private function hasLaterSubscription(ServiceSubscription $subscription): bool
    {
        return ServiceSubscription::query()
            ->where('user_id', $subscription->user_id)
            ->where('type', $subscription->type)
            ->whereKeyNot($subscription->id)
            ->whereIn('status', ['active', 'expired'])
            ->whereNotNull('ends_at')
            ->where('ends_at', '>', $subscription->ends_at)
            ->exists();
    }
}
