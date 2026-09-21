<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| دروازه‌ی دکمه‌ی اقدام
|--------------------------------------------------------------------------
|
| تا وقتی فیلدهای لازمِ یک فرم پر نشده‌اند، دکمه‌ی اقدام کم‌رنگ می‌ماند
| و بعد پُررنگ می‌شود. کاربر پیش از کلیک می‌فهمد چیزی مانده، به‌جای
| اینکه بزند و خطا بگیرد.
|
| ⚠️ رفتار واقعی در مرورگر بررسی شد (کرومیوم، ۱۸ بررسی: قفل اولیه،
| باز شدن با پر شدن فیلدها، قفل دوباره با ایمیل نامعتبر، رمز کوتاه،
| رمزهای نایکسان، عوض‌کردن تب، و نپریدن ارتفاع دکمه).
|
| آنچه اینجا قفل می‌شود، قلّاب‌هایی است که آن رفتار به آنها تکیه دارد:
| اگر data-gate یا required از مارک‌آپ بیفتد، اسکریپت بی‌صدا از کار
| می‌افتد و همه‌ی دکمه‌ها همیشه پُررنگ می‌مانند - بدون هیچ خطایی.
|
*/
class ButtonGateTest extends TestCase
{
    use RefreshDatabase;

    public static function gatedPages(): array
    {
        return [
            'ورود' => ['login'],
            'ثبت‌نام' => ['register'],
            'تماس با ما' => ['contact'],
        ];
    }

    #[DataProvider('gatedPages')]
    public function test_the_page_carries_both_halves_of_the_gate(string $route): void
    {
        $html = $this->get(route($route))->assertOk()->getContent();

        $this->assertStringContainsString('data-gate>', $html, 'فرم قلّاب data-gate ندارد.');
        $this->assertStringContainsString('data-gate-submit', $html, 'دکمه قلّاب data-gate-submit ندارد.');
    }

    /*
    | بدون required، دروازه چیزی برای سنجیدن ندارد و دکمه از همان
    | اول پُررنگ می‌ماند - یعنی قابلیت بی‌صدا خاموش می‌شود.
    */
    public function test_the_login_fields_are_marked_required(): void
    {
        $html = $this->get(route('login'))->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/name="email"[^>]*\srequired/s', $html);
        $this->assertMatchesRegularExpression('/id="login-password"[^>]*\srequired/s', $html);
    }

    public function test_the_register_confirmation_is_tied_to_the_password(): void
    {
        $html = $this->get(route('register'))->assertOk()->getContent();

        /*
        | رایج‌ترین خطای این فرم، نایکسان بودن دو رمز است. data-match
        | باعث می‌شود مرورگر همان لحظه بگوید، نه بعد از رفت‌وبرگشت با
        | سرور.
        */
        $this->assertStringContainsString('data-match="#register-password"', $html);

        // حداقل ۸ کاراکتر، همان قانونی که سرور هم دارد.
        $this->assertMatchesRegularExpression('/id="register-password"[^>]*minlength="8"/s', $html);
    }

    public function test_the_contact_form_requires_what_the_server_requires(): void
    {
        $html = $this->get(route('contact'))->assertOk()->getContent();

        /*
        | ایمیل دیگر اجباری نیست و جایش را تلفن گرفته: این سایت بازار
        | مصالح است و مخاطبش شماره دارد، نه لزوماً ایمیل.
        */
        foreach (['name', 'phone'] as $field) {
            $this->assertMatchesRegularExpression(
                '/name="' . $field . '"[^>]*\srequired/s',
                $html,
                "فیلد {$field} در مارک‌آپ required نیست، ولی سرور لازمش دارد."
            );
        }

        $this->assertDoesNotMatchRegularExpression(
            '/name="email"[^>]*\srequired/s',
            $html,
            'ایمیل دوباره اجباری شده است.'
        );

        $this->assertMatchesRegularExpression('/<textarea[^>]*name="message"[^>]*\srequired/s', $html);
    }

    /*
    | دکمه نباید disabled شود.
    |
    | دکمه‌ی disabled کلیک را می‌خورد و هیچ نمی‌گوید؛ کاربر می‌ماند که
    | «چرا کار نمی‌کند؟». دکمه‌ی کم‌رنگِ کلیک‌پذیر اعتبارسنجی مرورگر را
    | اجرا می‌کند و می‌گوید چه چیزی کم است.
    */
    #[DataProvider('gatedPages')]
    public function test_the_gate_never_disables_the_button(string $route): void
    {
        $html = $this->get(route($route))->assertOk()->getContent();

        $this->assertDoesNotMatchRegularExpression(
            '/<button[^>]*data-gate-submit[^>]*\sdisabled/s',
            $html
        );
    }

    /*
    | فرم ثبت آگهی چندمرحله‌ای است: هر دو دکمه‌ی «ادامه» و «ثبت آگهی»
    | باید از دروازه رد شوند، وگرنه کاربر در مرحله‌ی اول همان خطایی را
    | می‌گیرد که قرار بود نگیرد.
    */
    public function test_both_buttons_of_the_multi_step_ad_form_are_gated(): void
    {
        $user = \App\Models\User::create([
            'name' => 'کاربر', 'username' => 'sazande',
            'mobile' => '09120000020', 'password' => 'secret-password',
        ]);

        $html = $this->actingAs($user)->get(route('ad.create'))->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/id="nextStep"[^>]*data-gate-submit/s', $html);
        $this->assertMatchesRegularExpression('/id="submitStep"[^>]*data-gate-submit/s', $html);
    }

    /*
    |--------------------------------------------------------------------------
    | رمزنگهدار مرورگر
    |--------------------------------------------------------------------------
    |
    | مرورگر روی input[type=password] گزینه‌ی «کپی» را حذف می‌کند - یک
    | قاعده‌ی امنیتی است تا رمز وارد کلیپ‌بورد نشود، و در هر سایتی
    | همین‌طور است. پس کاربر نمی‌تواند رمز را از فیلد اول کپی و در
    | «تکرار رمز عبور» پیست کند.
    |
    | راهش کپی‌کردن نیست، رمزنگهدار است: با این صفت‌ها گوشی می‌فهمد
    | اینجا چه خبر است، رمز قوی پیشنهاد می‌دهد و هر دو فیلد را با هم
    | پر می‌کند. بدون آنها اصلاً پیشنهاد ذخیره هم نمی‌دهد.
    |
    | new-password روی هر دو فیلد ثبت‌نام لازم است؛ همین به مرورگر
    | می‌گوید این یک رمزِ تازه است، نه رمز موجود.
    */
    public function test_the_login_form_is_readable_by_a_password_manager(): void
    {
        $html = $this->get(route('login'))->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/name="email"[^>]*autocomplete="username"/s', $html);
        $this->assertMatchesRegularExpression('/id="login-password"[^>]*autocomplete="current-password"/s', $html);
    }

    public function test_both_register_password_fields_announce_a_new_password(): void
    {
        $html = $this->get(route('register'))->assertOk()->getContent();

        $this->assertMatchesRegularExpression(
            '/id="register-password"[^>]*autocomplete="new-password"/s',
            $html
        );

        $this->assertMatchesRegularExpression(
            '/id="register-password-confirmation"[^>]*autocomplete="new-password"/s',
            $html,
            'فیلد تکرار رمز new-password ندارد، پس رمزنگهدار آن را با هم پر نمی‌کند.'
        );
    }

    /*
    | و هیچ‌جا نباید چیزی کپی یا پیست را مسدود کند. بستن پیست روی فیلد
    | رمز یک ضدالگوی رایج است: کاربرِ رمزنگهدار را مجبور به تایپ دستی
    | می‌کند و نتیجه‌اش رمزهای کوتاه‌تر و ضعیف‌تر است.
    */
    public function test_nothing_blocks_pasting_into_the_password_fields(): void
    {
        foreach (['login', 'register'] as $route) {
            $html = $this->get(route($route))->assertOk()->getContent();

            $this->assertStringNotContainsString('onpaste', $html);
            $this->assertStringNotContainsString('oncopy', $html);
            $this->assertStringNotContainsString('oncut', $html);
        }
    }

    /*
    | خودِ کلاس هم باید در CSS تعریف شده باشد - وگرنه اسکریپت کلاسی
    | می‌گذارد که هیچ اثری ندارد و هیچ‌کس متوجه نمی‌شود.
    */
    public function test_the_locked_state_is_actually_styled(): void
    {
        $css = file_get_contents(base_path('../public_html/css/sazmat-theme.css'));

        $this->assertStringContainsString('.btn.is-locked {', $css);
        $this->assertStringContainsString('.btn-primary.is-locked {', $css);

        /*
        | opacity اندازه‌گیری شد و جواب نمی‌دهد: متن سفید روی نارنجیِ
        | محوشده حتی در ۰.۷۰ هم فقط ۲.۸:۱ کنتراست دارد. رنگ‌های
        | انتخاب‌شده ۵.۰۴:۱ و ۱۲.۹۰:۱ می‌دهند.
        */
        $this->assertDoesNotMatchRegularExpression(
            '/\.btn\.is-locked\s*\{[^}]*opacity/s',
            $css,
            'حالت قفل دوباره با opacity ساخته شده؛ متن ناخوانا می‌شود.'
        );
    }
}
