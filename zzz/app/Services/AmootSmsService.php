<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class AmootSmsService
{
    /*
    |--------------------------------------------------------------------------
    | کد یک‌بارمصرف ورود
    |--------------------------------------------------------------------------
    */
    public function sendOtp(string $mobile, string $code): void
    {
        $pattern = (int) config('services.amoot.pattern_code_id');

        if (! $pattern) {
            throw new RuntimeException('تنظیمات پیامک آموت کامل نشده است.');
        }

        $this->sendWithPattern($mobile, $pattern, $code);
    }

    /*
    |--------------------------------------------------------------------------
    | پیامک یادآوری تمدید اشتراک
    |--------------------------------------------------------------------------
    |
    | سامانه‌های پیامکی ایران معمولاً ارسال متن آزاد را فقط روی خط
    | خدماتی اجازه می‌دهند و در غیر این صورت باید «پترن» از قبل در پنل
    | تعریف و تأیید شده باشد.
    |
    | چون نمی‌دانیم پنل شما کدام حالت را دارد، هر دو پشتیبانی می‌شود:
    |
    |   اگر AMOOT_PATTERN_RENEWAL_ID در .env ست شده باشد → با پترن
    |   در غیر این صورت                                   → متن ساده
    |
    | مقدارهای پترن با «;» جدا می‌شوند؛ همان قالبی که آموت می‌پذیرد.
    |
    */
    public function sendRenewalReminder(string $mobile, string $message, array $patternValues = []): void
    {
        $pattern = (int) config('services.amoot.pattern_renewal_id');

        if ($pattern) {
            $this->sendWithPattern($mobile, $pattern, implode(';', $patternValues));
            return;
        }

        $this->sendText($mobile, $message);
    }

    /*
    |--------------------------------------------------------------------------
    | ارسال با پترن
    |--------------------------------------------------------------------------
    */
    private function sendWithPattern(string $mobile, int $patternId, string $values): void
    {
        [$token, $line] = $this->credentials();

        $response = Http::asForm()
            ->withHeaders(['Authorization' => $token])
            ->timeout(20)
            ->post('https://portal.amootsms.com/rest/SendWithPatternOWN', [
                'Mobile' => $mobile,
                'LineNumber' => $line,
                'PatternCodeID' => $patternId,
                'PatternValues' => $values,
            ]);

        $this->assertAccepted($response);
    }

    /*
    |--------------------------------------------------------------------------
    | ارسال متن ساده
    |--------------------------------------------------------------------------
    */
    private function sendText(string $mobile, string $message): void
    {
        [$token, $line] = $this->credentials();

        $response = Http::asForm()
            ->withHeaders(['Authorization' => $token])
            ->timeout(20)
            ->post('https://portal.amootsms.com/rest/SendSimple', [
                'Mobile' => $mobile,
                'LineNumber' => $line,
                'Message' => $message,
            ]);

        $this->assertAccepted($response);
    }

    private function credentials(): array
    {
        $token = config('services.amoot.token');
        $line = config('services.amoot.line_number');

        if (! $token || ! $line) {
            throw new RuntimeException('تنظیمات پیامک آموت کامل نشده است.');
        }

        return [$token, $line];
    }

    /*
    | آموت گاهی با کد ۲۰۰ ولی Status=false پاسخ می‌دهد، پس صرفِ
    | successful() بودن پاسخ کافی نیست.
    */
    private function assertAccepted($response): void
    {
        if (! $response->successful()) {
            throw new RuntimeException('ارسال پیامک ناموفق بود.');
        }

        $json = $response->json();

        if (
            is_array($json)
            && array_key_exists('Status', $json)
            && in_array($json['Status'], [false, 0, '0', 'false', 'False'], true)
        ) {
            throw new RuntimeException('سامانه پیامکی ارسال را تأیید نکرد.');
        }
    }
}
