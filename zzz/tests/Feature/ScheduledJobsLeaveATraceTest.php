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


    /*
    | ⚠️ جاسوس روی خودِ کانال، نه روی Log.
    |
    | دستورها با Log::channel('cron') می‌نویسند؛ اگر فقط Log را
    | جعل کنیم، تست حتی وقتی کانال اشتباه باشد هم سبز می‌ماند.
    */
    private function spyCronChannel(): \Mockery\MockInterface
    {
        $channel = \Mockery::spy(\Psr\Log\LoggerInterface::class);

        Log::shouldReceive('channel')->with('cron')->andReturn($channel);

        return $channel;
    }

    /*
    |--------------------------------------------------------------------------
    | کانال cron نباید تابع LOG_LEVEL باشد
    |--------------------------------------------------------------------------
    |
    | ⚠️ این همان چیزی است که خطِ لاگ را در تولید بی‌صدا می‌کرد.
    |
    | کانال پیش‌فرض level را از env می‌گیرد. اگر .env روی error باشد -
    | که در تولید معمول است - info و warning دور ریخته می‌شوند و
    | دوباره به همان سکوتی برمی‌گردیم که ماه‌ها مشکل را پنهان کرد.
    */
    public function test_the_cron_channel_ignores_the_env_log_level(): void
    {
        /* سطحش در خودِ فایل نوشته شده، نه از env خوانده می‌شود. */
        $config = file_get_contents(config_path('logging.php'));

        $this->assertMatchesRegularExpression(
            "/'cron'=>\[[^\]]*'level'=>'debug'/",
            $config,
            'سطح کانال cron باید ثابت باشد، نه env(LOG_LEVEL).'
        );

        $this->assertSame('debug', config('logging.channels.cron.level'));

        /* و فایلش جداست، تا کاربر یک فایل کوچک بفرستد نه کل لاگ. */
        $this->assertStringContainsString(
            'cron-tasks.log',
            config('logging.channels.cron.path')
        );
    }

    /*
    | و واقعاً بنویسد - حتی وقتی LOG_LEVEL روی error است.
    |
    | این تست به فایل واقعی می‌نویسد، چون همان چیزی است که کاربر
    | قرار است برایم بفرستد.
    */
    public function test_the_line_really_reaches_the_file_even_at_error_level(): void
    {
        config(['logging.channels.single.level' => 'error']);

        $path = config('logging.channels.cron.path');

        @unlink($path);

        $this->artisan('sazmat:cleanup')->assertSuccessful();

        $this->assertFileExists($path, 'فایل cron-tasks.log ساخته نشد.');

        $contents = file_get_contents($path);

        $this->assertStringContainsString('sazmat:cleanup', $contents);
        $this->assertStringContainsString('کد یک‌بارمصرف', $contents);

        @unlink($path);
    }

    public function test_the_cleanup_writes_what_it_did(): void
    {
        $channel = $this->spyCronChannel();

        $this->artisan('sazmat:cleanup')->assertSuccessful();

        $channel->shouldHaveReceived('info')
            ->once()
            ->withArgs(function ($message, $context = []) {
                return $message === 'sazmat:cleanup'
                    && array_key_exists('کد یک‌بارمصرف', $context)
                    && array_key_exists('اشتراک رهاشده', $context);
            });
    }

    public function test_the_reminder_run_writes_what_it_did(): void
    {
        $channel = $this->spyCronChannel();

        $this->artisan('sazmat:subscription-reminders')->assertSuccessful();

        $channel->shouldHaveReceived('info')
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
        $channel = $this->spyCronChannel();

        $this->artisan('sazmat:cleanup', ['--dry-run' => true])->assertSuccessful();

        $channel->shouldHaveReceived('info')
            ->once()
            ->withArgs(fn ($message, $context = []) => ($context['آزمایشی'] ?? null) === true);
    }
}
