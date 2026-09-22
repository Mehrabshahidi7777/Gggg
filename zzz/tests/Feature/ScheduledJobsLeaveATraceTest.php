<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| کارهای شبانه باید رد پا بگذارند
|--------------------------------------------------------------------------
|
| ⚠️ این تست از یک اشتباه واقعی آمده.
|
| دو دستورِ زمان‌بند ماه‌ها اجرا نشده بودند، چون proc_open روی هاست
| غیرفعال است و $schedule->command() پروسه‌ی جدا می‌خواهد. ولی
| cron.log می‌گفت «DONE» و خروجی دستورها هم به /dev/null می‌رفت -
| پس «کار می‌کند» و «هرگز اجرا نشده» از بیرون دقیقاً یک شکل داشتند.
|
| مدیرِ این سایت به دیتابیس دسترسیِ پرس‌وجو ندارد؛ فقط می‌تواند
| فایلِ لاگ را دانلود کند. پس تنها راهِ فهمیدنِ اینکه کاری انجام
| شده یا نه، همین یک خط در laravel.log است.
|
| بدون آن، دفعه‌ی بعد هم سکوت با موفقیت اشتباه گرفته می‌شود.
|
*/
class ScheduledJobsLeaveATraceTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_cleanup_writes_what_it_did(): void
    {
        Log::spy();

        $this->artisan('sazmat:cleanup')->assertSuccessful();

        Log::shouldHaveReceived('info')
            ->once()
            ->withArgs(function ($message, $context = []) {
                return $message === 'sazmat:cleanup'
                    && array_key_exists('کد یک‌بارمصرف', $context)
                    && array_key_exists('اشتراک رهاشده', $context);
            });
    }

    public function test_the_reminder_run_writes_what_it_did(): void
    {
        Log::spy();

        $this->artisan('sazmat:subscription-reminders')->assertSuccessful();

        Log::shouldHaveReceived('info')
            ->once()
            ->withArgs(function ($message, $context = []) {
                return $message === 'sazmat:subscription-reminders'
                    && array_key_exists('۷ روز مانده', $context);
            });
    }

    /*
    | و حالت آزمایشی هم باید در لاگ مشخص باشد - وگرنه یک اجرای
    | --dry-run که چیزی پاک نکرده، شبیه اجرای واقعیِ بی‌نتیجه
    | به نظر می‌رسد.
    */
    public function test_a_dry_run_says_so_in_the_log(): void
    {
        Log::spy();

        $this->artisan('sazmat:cleanup', ['--dry-run' => true])->assertSuccessful();

        Log::shouldHaveReceived('info')
            ->once()
            ->withArgs(fn ($message, $context = []) => ($context['آزمایشی'] ?? null) === true);
    }
}
