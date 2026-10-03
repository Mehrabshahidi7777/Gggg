<?php

namespace Tests\Feature;

use App\Services\AmootSmsService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| آنچه واقعاً به آموت می‌رود
|--------------------------------------------------------------------------
|
| تست‌های یادآوری، خودِ سرویس پیامک را جعل می‌کنند - یعنی هیچ‌کدام
| نمی‌دیدند درخواستِ نهایی چه شکلی است. جداکننده‌ی غلطِ مقادیر پترن
| دقیقاً در همان نقطه‌ی کور نشسته بود: کد یک‌بارمصرف یک مقدار بیشتر
| ندارد و هیچ‌وقت لو نمی‌داد.
|
| اینجا خودِ HTTP جعل می‌شود، پس شکل درخواست دیده می‌شود.
|
*/
class AmootPatternValuesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.amoot.token' => 'test-token',
            'services.amoot.line_number' => '30001234',
        ]);
    }

    /*
    | ⚠️ جداکننده «,» است، همان که نمونه‌های رسمی آموت دارند:
    |
    |     string.Join(",", PatternValues)   // C#
    |     "PatternValues=p1,p2"             // PHP
    |
    | با «;» هر سه مقدار داخل متغیر اول می‌نشست و پیامک می‌شد
    | «مهراب;خدمات;7 عزیز، اشتراک  شما تا  روز دیگر...».
    */
    public function test_pattern_values_are_joined_with_a_comma(): void
    {
        Http::fake([
            'portal.amootsms.com/*' => Http::response(['Status' => 'Success'], 200),
        ]);

        config(['services.amoot.pattern_renewal_id' => 55]);

        app(AmootSmsService::class)
            ->sendRenewalReminder('09121234567', 'متن پشتیبان', ['مهراب', 'خدمات', '7']);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'SendWithPatternOWN')
                && $request['PatternValues'] === 'مهراب,خدمات,7'
                && $request['PatternCodeID'] === 55;
        });
    }

    /*
    | و چون کاما جداکننده است، مقداری که خودش کاما دارد پیام را به هم
    | می‌ریزد: نامِ «رضایی, محمد» یک متغیر اضافه می‌سازد و بقیه یکی
    | می‌لغزند.
    */
    public function test_a_comma_inside_a_value_cannot_shift_the_others(): void
    {
        Http::fake([
            'portal.amootsms.com/*' => Http::response(['Status' => 'Success'], 200),
        ]);

        config(['services.amoot.pattern_renewal_id' => 55]);

        app(AmootSmsService::class)
            ->sendRenewalReminder('09121234567', 'متن', ['رضایی, محمد', 'خدمات', '7']);

        Http::assertSent(function ($request) {
            return substr_count($request['PatternValues'], ',') === 2;
        });
    }

    /*
    | بدون شناسه‌ی پترن، متن ساده می‌رود. این مسیر برای وقتی است که
    | خط خدماتی ارسال آزاد را اجازه بدهد.
    */
    public function test_without_a_pattern_id_the_plain_text_is_sent(): void
    {
        Http::fake([
            'portal.amootsms.com/*' => Http::response(['Status' => 'Success'], 200),
        ]);

        config(['services.amoot.pattern_renewal_id' => null]);

        app(AmootSmsService::class)
            ->sendRenewalReminder('09121234567', 'متن کامل پیامک', ['مهراب', 'خدمات', '7']);

        Http::assertSent(fn ($request) => str_contains($request->url(), 'SendSimple')
            && $request['Message'] === 'متن کامل پیامک');
    }

    /*
    | یک پترن برای هر سه مرحله. آنچه عوض می‌شود متغیر سوم است:
    | یک عبارت، نه عدد روز.
    */
    public function test_one_pattern_carries_every_stage(): void
    {
        Http::fake([
            'portal.amootsms.com/*' => Http::response(['Status' => 'Success'], 200),
        ]);

        config(['services.amoot.pattern_renewal_id' => 55]);

        $service = app(AmootSmsService::class);

        foreach (['تا 7 روز دیگر تمام می‌شود', 'فردا تمام می‌شود', 'تمام شد و آگهی‌هایتان تعلیق شدند'] as $state) {
            $service->sendRenewalReminder('09121234567', 'متن', ['مهراب', 'خدمات', $state]);
        }

        Http::assertSentCount(3);

        Http::assertSent(fn ($request) => $request['PatternCodeID'] === 55
            && $request['PatternValues'] === 'مهراب,خدمات,تمام شد و آگهی‌هایتان تعلیق شدند');
    }

    /*
    | آموت گاهی با کد ۲۰۰ ولی Status=false جواب می‌دهد. اگر این را
    | قبول کنیم، کرون ستون _sent_at را پر می‌کند و آن پیامک دیگر
    | هرگز فرستاده نمی‌شود.
    |
    | حالا که متن ساده پشتوانه‌ی پترن است، شکستِ واقعی یعنی هر دو
    | شکست بخورند.
    */
    public function test_a_two_hundred_with_status_false_is_still_a_failure(): void
    {
        Http::fake([
            'portal.amootsms.com/*' => Http::response(['Status' => false], 200),
        ]);

        config(['services.amoot.pattern_renewal_id' => 55]);

        $this->expectException(\RuntimeException::class);

        app(AmootSmsService::class)->sendRenewalReminder('09121234567', 'متن', ['مهراب']);
    }

    /*
    |--------------------------------------------------------------------------
    | پترنِ تأییدنشده نباید یادآوری را خاموش کند
    |--------------------------------------------------------------------------
    |
    | ⚠️ این از یک وضعیت واقعی آمده: مهراب پترن را ساخت و شناسه‌اش را
    | همان روز در .env گذاشت، در حالی که آموت هنوز تأییدش نکرده بود.
    |
    | قبلاً نتیجه‌اش «هیچ پیامکی» بود - چون شناسه ست بود، متن ساده
    | اصلاً امتحان نمی‌شد. یعنی گذاشتن شناسه پیش از تأیید،
    | یادآوری‌ها را تا روز تأیید بی‌صدا خاموش می‌کرد.
    */
    public function test_a_rejected_pattern_falls_back_to_plain_text(): void
    {
        Http::fake([
            '*SendWithPatternOWN' => Http::response(['Status' => false], 200),
            '*SendSimple' => Http::response(['Status' => 'Success'], 200),
        ]);

        config(['services.amoot.pattern_renewal_id' => 55]);

        app(AmootSmsService::class)
            ->sendRenewalReminder('09121234567', 'اشتراک خدمات شما فردا تمام می‌شود', ['خدمات', 'فردا تمام می‌شود']);

        Http::assertSent(fn ($request) => str_contains($request->url(), 'SendSimple')
            && $request['Message'] === 'اشتراک خدمات شما فردا تمام می‌شود'
            && $request['Mobile'] === '09121234567');
    }

    /*
    | و وقتی پترن کار می‌کند، متن ساده نباید هم برود - وگرنه کاربر
    | دو پیامک می‌گیرد و هزینه دو برابر می‌شود.
    */
    public function test_a_working_pattern_does_not_also_send_plain_text(): void
    {
        Http::fake([
            'portal.amootsms.com/*' => Http::response(['Status' => 'Success'], 200),
        ]);

        config(['services.amoot.pattern_renewal_id' => 55]);

        app(AmootSmsService::class)->sendRenewalReminder('09121234567', 'متن', ['خدمات', 'فردا']);

        Http::assertSentCount(1);
        Http::assertNotSent(fn ($request) => str_contains($request->url(), 'SendSimple'));
    }

    /*
    |--------------------------------------------------------------------------
    | ⚠️ ولی قطعیِ ارتباط پشتوانه ندارد
    |--------------------------------------------------------------------------
    |
    | پاسخِ رد یعنی مطمئنیم چیزی نرفته، پس ارسال دوباره بی‌خطر است.
    | ولی تایم‌اوت یعنی نمی‌دانیم درخواست به آموت رسیده یا نه - شاید
    | پیامک رفته و فقط پاسخش گم شده.
    |
    | اگر آنجا هم متن ساده بفرستیم، کاربر دو پیامک می‌گیرد. پس خطا
    | بالا می‌رود و دستور فردا دوباره امتحان می‌کند (ستون _sent_at
    | علامت نمی‌خورد).
    */
    public function test_a_timeout_does_not_trigger_a_second_message(): void
    {
        Http::fake(function () {
            throw new \Illuminate\Http\Client\ConnectionException('timed out');
        });

        config(['services.amoot.pattern_renewal_id' => 55]);

        try {
            app(AmootSmsService::class)->sendRenewalReminder('09121234567', 'متن', ['خدمات', 'فردا']);
            $this->fail('خطای قطعیِ ارتباط باید بالا می‌رفت.');
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            // همین درست است.
        }

        Http::assertNotSent(fn ($request) => str_contains($request->url(), 'SendSimple'));
    }

    /*
    | و بدون شناسه، همان رفتار قبلی: مستقیم متن ساده.
    */
    public function test_an_empty_pattern_id_still_sends_plain_text(): void
    {
        Http::fake([
            'portal.amootsms.com/*' => Http::response(['Status' => 'Success'], 200),
        ]);

        config(['services.amoot.pattern_renewal_id' => null]);

        app(AmootSmsService::class)->sendRenewalReminder('09121234567', 'متن پشتیبان', ['خدمات', 'فردا']);

        Http::assertSent(fn ($request) => str_contains($request->url(), 'SendSimple')
            && $request['Message'] === 'متن پشتیبان');
    }
}
