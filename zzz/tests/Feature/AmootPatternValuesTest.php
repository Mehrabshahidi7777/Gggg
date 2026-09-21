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
    | دو پترن جدا. اگر «تمام شد» از شناسه‌ی یادآوری استفاده کند،
    | کاربر پیامکِ «تا ۰ روز دیگر» می‌گیرد.
    */
    public function test_the_expiry_notice_uses_its_own_pattern(): void
    {
        Http::fake([
            'portal.amootsms.com/*' => Http::response(['Status' => 'Success'], 200),
        ]);

        config([
            'services.amoot.pattern_renewal_id' => 55,
            'services.amoot.pattern_expired_id' => 66,
        ]);

        app(AmootSmsService::class)
            ->sendExpiryNotice('09121234567', 'متن', ['مهراب', 'خدمات']);

        Http::assertSent(fn ($request) => $request['PatternCodeID'] === 66
            && $request['PatternValues'] === 'مهراب,خدمات');
    }

    /*
    | و اگر پترنِ «تمام شد» ساخته نشده باشد، نباید سراغ پترن یادآوری
    | برود؛ متن ساده می‌رود که دست‌کم درست است.
    */
    public function test_a_missing_expiry_pattern_falls_back_to_plain_text(): void
    {
        Http::fake([
            'portal.amootsms.com/*' => Http::response(['Status' => 'Success'], 200),
        ]);

        config([
            'services.amoot.pattern_renewal_id' => 55,
            'services.amoot.pattern_expired_id' => null,
        ]);

        app(AmootSmsService::class)
            ->sendExpiryNotice('09121234567', 'اشتراک شما تمام شد.', ['مهراب', 'خدمات']);

        Http::assertSent(fn ($request) => str_contains($request->url(), 'SendSimple'));
    }

    /*
    | آموت گاهی با کد ۲۰۰ ولی Status=false جواب می‌دهد. اگر این را
    | قبول کنیم، کرون ستون _sent_at را پر می‌کند و آن پیامک دیگر
    | هرگز فرستاده نمی‌شود.
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
}
