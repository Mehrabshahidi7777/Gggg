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
    |   اگر شناسه‌ی پترن در .env ست شده باشد → با پترن
    |   در غیر این صورت                      → متن ساده
    |
    | سه متغیر: نام کاربر، نوع اشتراک، و وضعیت.
    |
    | ⚠️ متغیر سوم عمداً یک *عبارت* است، نه تعداد روز.
    |
    | اگر عدد بود، هر سه مرحله نمی‌توانستند از یک پترن استفاده کنند:
    | مرحله‌ی «تمام شد» پیامکِ «تا ۰ روز دیگر تمام می‌شود» می‌داد - هم
    | غلط، هم برعکسِ کاری که باید بکند. با عبارت، یک پترن هر سه مرحله
    | را درست می‌گوید و کاربر فقط یک شناسه در پنل می‌سازد:
    |
    |   «تا 7 روز دیگر تمام می‌شود»
    |   «فردا تمام می‌شود»
    |   «تمام شد و آگهی‌هایتان تعلیق شدند»
    |
    */
    public function sendRenewalReminder(string $mobile, string $message, array $patternValues = []): void
    {
        $pattern = (int) config('services.amoot.pattern_renewal_id');

        if ($pattern) {
            $this->sendWithPattern($mobile, $pattern, $this->joinValues($patternValues));
            return;
        }

        $this->sendText($mobile, $message);
    }

    /*
    |--------------------------------------------------------------------------
    | چسباندن مقدارهای پترن
    |--------------------------------------------------------------------------
    |
    | ⚠️ جداکننده «,» است نه «;». هر دو نمونه‌ی رسمی خود آموت
    | (github.com/AmootSoft/AmootSMS) با کاما می‌چسبانند:
    |
    |     string.Join(",", PatternValues)        // C#
    |     "PatternValues=p1,p2"                  // PHP
    |
    | با جداکننده‌ی غلط، هر سه مقدار داخل متغیر اول می‌نشینند و بقیه
    | خالی می‌مانند. کد یک‌بارمصرف چون یک مقدار بیشتر ندارد این را لو
    | نمی‌داد؛ اولین پیامک یادآوری لو می‌داد.
    |
    | و چون کاما جداکننده است، خودِ مقدارها نباید کاما داشته باشند -
    | وگرنه یک نامِ «رضایی, محمد» پیام را به هم می‌ریزد. کامای لاتین
    | به کامای فارسی تبدیل می‌شود که در متن پیامک هم درست‌تر است.
    |
    */
    private function joinValues(array $values): string
    {
        return implode(',', array_map(
            fn ($value) => str_replace([',', ';'], '،', (string) $value),
            $values
        ));
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
