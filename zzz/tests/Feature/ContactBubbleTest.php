<?php

namespace Tests\Feature;

use App\Models\ContactMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| حباب گفت‌وگو
|--------------------------------------------------------------------------
|
| تا امروز تنها راه پیام دادن، رفتن به صفحه‌ی «تماس با ما» بود - یعنی
| کاربر باید کاری که می‌کرد را رها می‌کرد. حالا دکمه‌ای در هر صفحه
| همراهش است و همان فرم را همان‌جا باز می‌کند.
|
| ⚠️ رفتار باز و بسته شدن در کرومیوم بررسی شد. آنچه اینجا تست می‌شود:
| حضور در صفحه‌ها، نبودش آنجا که تکراری است، و اینکه مسیر ارسال با
| JSON هم کار کند - و تله‌ی ربات آنجا هم برقرار باشد.
|
*/
class ContactBubbleTest extends TestCase
{
    use RefreshDatabase;

    public static function pages(): array
    {
        return [
            'خانه' => ['home'],
            'محصولات' => ['products'],
            'خدمات' => ['services'],
            'درباره ما' => ['about'],
        ];
    }

    #[DataProvider('pages')]
    public function test_the_bubble_is_on_every_page(string $route): void
    {
        /*
        | نشانه‌ای که فقط در مارک‌آپ حباب هست.
        |
        | اول data-chat-toggle را گرفته بودم، ولی همان رشته در اسکریپت
        | لایه هم هست (querySelector) و اسکریپت همیشه در صفحه است -
        | پس تست چه حباب می‌آمد چه نمی‌آمد سبز می‌شد.
        */
        $this->get(route($route))
            ->assertOk()
            ->assertSee('chat-bubble__fab', false)
            ->assertSee('پیام به سازمت', false);
    }

    /*
    | جز صفحه‌ی «تماس با ما»، که خودِ فرم رویش است و دکمه فقط تکرار
    | می‌شد.
    */
    public function test_the_bubble_is_not_shown_on_the_contact_page(): void
    {
        $this->get(route('contact'))
            ->assertOk()
            ->assertDontSee('chat-bubble__fab', false);
    }

    /*
    |--------------------------------------------------------------------------
    | ارسال
    |--------------------------------------------------------------------------
    */
    public function test_a_message_sent_from_the_bubble_is_stored(): void
    {
        /*
        | بدون ایمیل - که حالا اختیاری است. همین تست اگر ایمیل
        | می‌فرستاد، قانون تازه را اصلاً امتحان نمی‌کرد.
        */
        $this->postJson(route('contact.store'), [
            'name' => 'مهراب',
            'phone' => '09121234567',
            'message' => 'یک سؤال داشتم.',
            'opened_at' => encrypt(time() - 30),
        ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertSame(1, ContactMessage::count());
        $this->assertSame('مهراب', ContactMessage::first()->name);
    }

    /*
    | خطای اعتبارسنجی باید ۴۲۲ با فهرست خطاها باشد، نه ریدایرکت -
    | وگرنه اسکریپت چیزی برای نشان دادن ندارد و کاربر فکر می‌کند
    | دکمه خراب است.
    */
    public function test_a_validation_error_comes_back_as_json(): void
    {
        $this->postJson(route('contact.store'), [
            'name' => 'مهراب',
            'opened_at' => encrypt(time() - 30),
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['phone', 'message']);

        $this->assertSame(0, ContactMessage::count());
    }

    /*
    | تله‌ی ربات باید در این مسیر هم کار کند - وگرنه ربات‌ها فقط
    | مسیرشان را عوض می‌کنند.
    */
    public function test_the_spam_trap_also_guards_the_json_path(): void
    {
        $this->postJson(route('contact.store'), [
            'name' => 'Robertluh',
            'phone' => '09121234567',
            'email' => 'spam@example.com',
            'message' => 'buy now',
            'website' => 'http://spam.example.com',
            'opened_at' => encrypt(time() - 30),
        ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertSame(0, ContactMessage::count());
    }

    /*
    | و فرم صفحه‌ی «تماس با ما» هنوز معمولی ارسال می‌شود. افزودن
    | پاسخ JSON نباید آن را شکسته باشد.
    */
    public function test_the_plain_form_still_redirects_back(): void
    {
        $this->post(route('contact.store'), [
            'name' => 'مهراب',
            'phone' => '09121234567',
            'message' => 'یک سؤال داشتم.',
            'opened_at' => encrypt(time() - 30),
        ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame(1, ContactMessage::count());
    }

    /*
    |--------------------------------------------------------------------------
    | خودِ حباب
    |--------------------------------------------------------------------------
    */
    public function test_the_bubble_carries_the_spam_trap(): void
    {
        $html = $this->get(route('home'))->assertOk()->getContent();

        $this->assertStringContainsString('name="website"', $html);
        $this->assertStringContainsString('name="opened_at"', $html);
    }

    /*
    | پنل با hidden بسته می‌ماند، نه با CSS. اگر باز بماند، روی هر
    | صفحه‌ای یک کادر بزرگ جلوی محتوا را می‌گیرد.
    */
    public function test_the_panel_starts_closed(): void
    {
        $html = $this->get(route('home'))->assertOk()->getContent();

        $this->assertMatchesRegularExpression(
            '/id="chat-bubble-panel"[^>]*hidden/s',
            $html
        );

        $this->assertStringContainsString('aria-expanded="false"', $html);
    }

    /*
    |--------------------------------------------------------------------------
    | برچسب راهنما
    |--------------------------------------------------------------------------
    |
    | دایره‌ی تنها برای کسی که این الگو را ندیده مبهم است. یک برچسب
    | کوچک بالایش می‌گوید آنجا چه خبر است و با اولین اسکرول می‌رود.
    |
    | ⚠️ رفتارش (ظاهر شدن، رفتن با اسکرول، یک‌بار در هر نشست) در
    | کرومیوم اندازه‌گیری شد. آنچه اینجا قفل می‌شود، قلاب‌هایی است که
    | آن رفتار بدون‌شان بی‌صدا می‌میرد.
    */
    public function test_the_hint_sits_in_the_markup_and_starts_hidden(): void
    {
        $html = $this->get(route('home'))->assertOk()->getContent();

        $this->assertMatchesRegularExpression(
            '/<button[^>]*data-chat-hint[^>]*hidden[^>]*>\s*ارتباط با ما/u',
            $html,
            'برچسب باید با hidden شروع شود؛ نمایشش کار جاوااسکریپت است.'
        );
    }

    /*
    | مهم‌ترین تستِ این تغییر.
    |
    | اسکریپت لایه با querySelector('[data-chat-toggle]') دکمه‌ی گرد را
    | پیدا می‌کرد. حالا دو عنصر آن نشانه را دارند و querySelector اولی
    | را برمی‌گرداند - یعنی برچسب. آن‌وقت aria-expanded روی برچسب
    | می‌نشست و فوکوسِ بعد از بستن به جای نامرئی می‌رفت.
    |
    | پس دکمه‌ی گرد نشانه‌ی خودش را دارد.
    */
    public function test_the_round_button_keeps_a_hook_of_its_own(): void
    {
        $html = $this->get(route('home'))->assertOk()->getContent();

        $this->assertMatchesRegularExpression(
            '/<button[^>]*class="chat-bubble__fab"[^>]*data-chat-fab/s',
            $html,
            'دکمه‌ی گرد باید data-chat-fab داشته باشد.'
        );

        $this->assertStringContainsString(
            "root.querySelector('[data-chat-fab]')",
            $html,
            'اسکریپت باید دکمه‌ی گرد را با نشانه‌ی خودش بگیرد، نه با data-chat-toggle.'
        );

        /*
        | و کلیک باید به هر دو وصل شود، وگرنه برچسب دکمه‌ای است که
        | هیچ کاری نمی‌کند.
        */
        $this->assertStringContainsString(
            "root.querySelectorAll('[data-chat-toggle]')",
            $html
        );
    }

    /*
    | همان دام بالا: data-chat-hint در اسکریپت لایه هم هست و اسکریپت
    | روی هر صفحه‌ای می‌آید. نشانه‌ای لازم است که فقط در مارک‌آپ باشد.
    */
    public function test_the_hint_is_not_on_the_contact_page(): void
    {
        $this->get(route('contact'))
            ->assertOk()
            ->assertDontSee('class="chat-bubble__hint"', false);
    }

    public function test_the_bubble_is_styled(): void
    {
        $css = file_get_contents(base_path('../public_html/css/sazmat-theme.css'));

        $this->assertStringContainsString('.chat-bubble {', $css);

        /*
        | سایت RTL است، پس inset-inline-end دکمه را سمت چپ می‌برد.
        | خواسته سمت راست بود.
        */
        $this->assertMatchesRegularExpression(
            '/\.chat-bubble\s*\{[^}]*\bright:/s',
            $css,
            'دکمه باید با right جای‌گذاری شود، نه inset-inline-end.'
        );
    }

    /*
    | ⚠️ این یکی هم دام RTL است، و در کرومیوم ۲۱ پیکسل جابه‌جایی
    | نشان داد.
    |
    | ظرفِ حباب، ستونی با align-items: flex-end است - و در صفحه‌ی
    | راست‌به‌چپ، flex-end یعنی چپ. برچسب از دایره پهن‌تر است، پس اگر
    | در جریان بماند عرض ظرف را باز می‌کند و دایره را از گوشه‌ی راست
    | به وسط صفحه هل می‌دهد.
    |
    | position: absolute آن را بیرون از جریان نگه می‌دارد.
    */
    public function test_the_hint_is_taken_out_of_the_flow(): void
    {
        $css = file_get_contents(base_path('../public_html/css/sazmat-theme.css'));

        $this->assertMatchesRegularExpression(
            '/\.chat-bubble__hint\s*\{[^}]*position:\s*absolute/s',
            $css,
            'برچسب باید absolute باشد، وگرنه دایره را از گوشه جابه‌جا می‌کند.'
        );

        /* و با hidden پنهان شود، چون جاوااسکریپت با همان کار می‌کند. */
        $this->assertStringContainsString('.chat-bubble__hint[hidden] { display: none; }', $css);
    }
}
