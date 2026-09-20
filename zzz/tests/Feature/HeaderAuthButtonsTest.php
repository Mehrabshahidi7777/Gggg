<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| دکمه‌های «ورود» و «ثبت‌نام» در هدر
|--------------------------------------------------------------------------
|
| این دو کنار هم می‌نشینند و هم‌رده‌اند، پس چشم انتظار دارد هم‌اندازه
| باشند - ولی نه پهن‌تر از آنچه متنشان لازم دارد.
|
| تاریخچه‌ی این دو دکمه کوتاه و پر از برگشت است: اول با padding متفاوت
| نامساوی بودند، بعد min-width: 96px هم‌اندازه‌شان کرد و به جایش هر دو
| را گشاد کرد - آن‌قدر که روی نمایشگر ۳۶۰ پیکسلی نوار هدر جا نمی‌شد و
| کل صفحه افقی اسکرول می‌خورد (اندازه‌گیری‌شده: عرض محتوا ۳۶۸ پیکسل).
|
| حالا یک گرید دو ستونه این کار را بدون عدد جادویی می‌کند. چیزی که این
| تست قفل می‌کند خودِ ظرف است: بدون آن، گرید وجود ندارد و دو دکمه
| بی‌صدا به عرض متنِ خودشان برمی‌گردند - یعنی دوباره نامساوی.
|
| ⚠️ آنچه اینجا تست نمی‌شود: عرض واقعیِ رندرشده. آن در مرورگر
| اندازه‌گیری شد؛ اینجا فقط ساختاری که آن رفتار به آن تکیه دارد.
|
*/
class HeaderAuthButtonsTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_guest_sees_both_buttons_inside_the_sizing_group(): void
    {
        $html = $this->get(route('home'))->assertOk()->getContent();

        $this->assertStringContainsString('header-auth-group', $html);

        // ظرف باید *هر دو* را در بر بگیرد، نه یکی را.
        $this->assertMatchesRegularExpression(
            '/<div class="header-auth-group">.*?ورود.*?ثبت‌نام.*?<\/div>/su',
            $html,
            'دو دکمه داخل یک ظرف هم‌اندازه‌کننده نیستند.'
        );
    }

    /*
    | عدد ثابت همان چیزی بود که دکمه‌ها را گشاد می‌کرد. برگشتنش یعنی
    | برگشتن همان باگ.
    */
    public function test_the_buttons_are_not_padded_out_to_a_fixed_width(): void
    {
        $css = file_get_contents(base_path('../public_html/css/sazmat-theme.css'));

        $this->assertStringNotContainsString(
            '.header-auth { min-width:',
            $css,
            'سقف ثابت دوباره روی دکمه‌ها نشسته است.'
        );

        $this->assertStringContainsString('.header-auth-group {', $css);
        $this->assertStringContainsString('grid-auto-columns: 1fr;', $css);
    }

    /*
    | گروه نباید زیر فشارِ فلکس جمع شود؛ اگر جمع شود دو ستون به یک
    | نسبت کوچک نمی‌شوند و دوباره نامساوی می‌افتند.
    */
    public function test_the_group_does_not_shrink_under_flex_pressure(): void
    {
        $css = file_get_contents(base_path('../public_html/css/sazmat-theme.css'));

        $this->assertMatchesRegularExpression(
            '/\.header-auth-group\s*\{[^}]*flex-shrink:\s*0/s',
            $css
        );
    }

    /*
    | کاربرِ واردشده اصلاً این دو را نمی‌بیند - جایش منوی حساب است.
    */
    public function test_a_signed_in_user_sees_no_auth_buttons(): void
    {
        $user = User::create([
            'name' => 'کاربر',
            'username' => 'member',
            'mobile' => '09120000009',
            'password' => 'secret-password',
        ]);

        $this->actingAs($user)
            ->get(route('home'))
            ->assertOk()
            ->assertDontSee('header-auth-group', false);
    }
}
