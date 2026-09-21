<?php

namespace Tests\Feature;

use App\Models\ContactMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| تله‌ی ربات روی فرم تماس
|--------------------------------------------------------------------------
|
| ربات‌ها این فرم را پیدا کرده بودند: از شش پیام موجود در دیتابیس، پنج
| تا تبلیغات خودکار بود («Robertluh», «Dear … Webmaster»).
|
| بدون کپچا، چون کپچا را آدمِ واقعی هم باید حل کند و برای فرمی که ماهی
| چند پیام واقعی می‌گیرد هزینه‌اش بیشتر از فایده‌اش است.
|
| دو نشانه که آدم هرگز تولیدش نمی‌کند:
|
|   ۱. فیلد پنهانی که پر شده باشد
|   ۲. ارسال در کمتر از سه ثانیه بعد از باز شدن فرم
|
| ⚠️ و مهم‌ترین قاعده: به ربات گفته نمی‌شود که گیر افتاده. همان پیام
| موفقیت برمی‌گردد و فقط چیزی ذخیره نمی‌شود - وگرنه نویسنده‌ی ربات
| می‌فهمد کجا گیر کرده و دورش می‌زند.
|
*/
class ContactSpamTrapTest extends TestCase
{
    use RefreshDatabase;

    /*
    |--------------------------------------------------------------------------
    | آدمِ واقعی نباید اذیت شود
    |--------------------------------------------------------------------------
    |
    | این مهم‌ترین گروه است. رد شدن یک اسپم خیلی کم‌هزینه‌تر از مسدود
    | کردن یک مشتری واقعی است.
    */
    public function test_a_real_person_gets_through(): void
    {
        $this->post(route('contact.store'), $this->message([
            'opened_at' => encrypt(time() - 30),
        ]))->assertSessionHasNoErrors();

        $this->assertSame(1, ContactMessage::count());
        $this->assertSame('مهراب', ContactMessage::first()->name);
    }

    /*
    | فرمی که از کش مرورگر آمده یا نسخه‌ی قدیمی است، مهر زمان ندارد.
    | این نباید مسدود شود.
    */
    public function test_a_missing_timestamp_does_not_block_anyone(): void
    {
        $this->post(route('contact.store'), $this->message())
            ->assertSessionHasNoErrors();

        $this->assertSame(1, ContactMessage::count());
    }

    public function test_a_tampered_timestamp_does_not_block_anyone(): void
    {
        $this->post(route('contact.store'), $this->message([
            'opened_at' => 'دست‌کاری‌شده',
        ]));

        $this->assertSame(1, ContactMessage::count());
    }

    /*
    | فیلد تله خالی است، که حالت عادی هر آدمی است.
    */
    public function test_an_empty_honeypot_is_normal(): void
    {
        $this->post(route('contact.store'), $this->message([
            'website' => '',
            'opened_at' => encrypt(time() - 30),
        ]));

        $this->assertSame(1, ContactMessage::count());
    }

    /*
    |--------------------------------------------------------------------------
    | ربات
    |--------------------------------------------------------------------------
    */
    public function test_a_filled_honeypot_is_dropped(): void
    {
        $this->post(route('contact.store'), $this->message([
            'website' => 'http://spam.example.com',
            'opened_at' => encrypt(time() - 30),
        ]));

        $this->assertSame(0, ContactMessage::count());
    }

    public function test_an_instant_submission_is_dropped(): void
    {
        $this->post(route('contact.store'), $this->message([
            'opened_at' => encrypt(time()),
        ]));

        $this->assertSame(0, ContactMessage::count());
    }

    /*
    | ربات باید فکر کند کارش گرفته است.
    */
    public function test_the_bot_is_told_it_succeeded(): void
    {
        $this->post(route('contact.store'), $this->message([
            'website' => 'http://spam.example.com',
        ]))
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success', 'پیام شما با موفقیت ارسال شد.');
    }

    /*
    | و فیلد تله نباید هرگز در دیتابیس بنشیند، حتی اگر روزی کسی به
    | قوانین اعتبارسنجی اضافه‌اش کند.
    */
    public function test_the_honeypot_value_is_never_stored(): void
    {
        $this->post(route('contact.store'), $this->message([
            'website' => '',
            'opened_at' => encrypt(time() - 30),
        ]));

        $this->assertArrayNotHasKey('website', ContactMessage::first()->getAttributes());
    }

    /*
    |--------------------------------------------------------------------------
    | خودِ فرم
    |--------------------------------------------------------------------------
    */
    public function test_the_form_carries_the_trap_without_showing_it(): void
    {
        $html = $this->get(route('contact'))->assertOk()->getContent();

        $this->assertStringContainsString('name="website"', $html);
        $this->assertStringContainsString('name="opened_at"', $html);

        // پنهان از چشم و از صفحه‌خوان، و بیرون از مسیر Tab
        $this->assertStringContainsString('class="hp-field" aria-hidden="true"', $html);
        $this->assertStringContainsString('tabindex="-1"', $html);
    }

    /*
    | تله نباید required باشد، وگرنه دروازه‌ی دکمه‌ی ارسال هرگز باز
    | نمی‌شود و آدم واقعی هم نمی‌تواند پیام بدهد.
    */
    public function test_the_trap_is_not_required(): void
    {
        $html = $this->get(route('contact'))->assertOk()->getContent();

        $this->assertDoesNotMatchRegularExpression(
            '/name="website"[^>]*\srequired/s',
            $html
        );
    }

    /*
    | و خودِ کلاس باید واقعاً پنهانش کند، وگرنه کاربر یک فیلد عجیب
    | «وب‌سایت» وسط فرم می‌بیند.
    */
    public function test_the_trap_is_actually_hidden_by_css(): void
    {
        $css = file_get_contents(base_path('../public_html/css/sazmat-theme.css'));

        $this->assertMatchesRegularExpression('/\.hp-field\s*\{[^}]*position:\s*absolute/s', $css);
    }

    private function message(array $extra = []): array
    {
        return array_merge([
            'name' => 'مهراب',
            'email' => 'mehrab@example.com',
            'phone' => '09121234567',
            'subject' => 'سؤال',
            'message' => 'سلام، یک سؤال داشتم.',
        ], $extra);
    }
}
