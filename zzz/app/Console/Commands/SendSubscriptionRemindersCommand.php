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
            | دیگر تمام می‌شود. چون کرون روزانه است، «<=» کافی است و
            | نیازی به بازه‌ی دقیق نیست؛ ستون _sent_at جلوی تکرار را
            | می‌گیرد.
            */
            $query->where('status', 'active')
                ->where('ends_at', '>', $now)
                ->where('ends_at', '<=', $now->copy()->addDays($daysLeft));
        } else {
            $query->where('ends_at', '<=', $now)
                ->whereIn('status', ['active', 'expired']);
        }

        $count = 0;

        $query->chunkById(100, function ($subscriptions) use ($sms, $dry, $column, $daysLeft, &$count) {

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

                $mobile = $subscription->user?->mobile;

                if (! $mobile) {
                    $subscription->forceFill([$column => now()])->save();
                    continue;
                }

                $typeLabel = $subscription->type === 'product' ? 'محصولات' : 'خدمات';
                $name = $subscription->user->username ?: $subscription->user->name;

                /*
                | وضعیت، به شکل یک عبارت کامل.
                |
                | ⚠️ این همان چیزی است که یک پترن را برای هر سه مرحله
                | کافی می‌کند. اگر به‌جایش عدد روز می‌رفت، مرحله‌ی
                | آخر پیامکِ «تا ۰ روز دیگر» می‌داد و ناچار بودیم
                | پترن دومی بسازیم.
                |
                | «فردا» هم از «تا ۱ روز دیگر» فارسی‌تر است.
                */
                $state = match (true) {
                    $daysLeft === 0 => 'تمام شد و آگهی‌هایتان تعلیق شدند',
                    $daysLeft === 1 => 'فردا تمام می‌شود',
                    default => sprintf('تا %d روز دیگر تمام می‌شود', $daysLeft),
                };

                /*
                | متن پشتیبان، برای وقتی که پترنی ساخته نشده باشد.
                | اینجا جا هست، پس مهلت شش‌ماهه هم گفته می‌شود.
                */
                $message = $daysLeft > 0
                    ? sprintf(
                        'سازمت | %s عزیز، اشتراک %s شما %s. برای جلوگیری از تعلیق آگهی‌ها آن را تمدید کنید. sazmat.com',
                        $name,
                        $typeLabel,
                        $state
                    )
                    : sprintf(
                        'سازمت | %s عزیز، اشتراک %s شما به پایان رسید و آگهی‌هایتان تعلیق شد. تا شش ماه فرصت دارید با تمدید، آنها را بازگردانید. sazmat.com',
                        $name,
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
                    | متغیر سوم ($state) است، نه در پترن.
                    */
                    $sms->sendRenewalReminder($mobile, $message, [
                        $name,
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
