<?php

namespace Tests\Feature;

use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| کانال روبیکا در فوتر
|--------------------------------------------------------------------------
|
| یک سطر در بخش «تماس» فوتر، زیر شماره‌ی تلفن: لوگوی روبیکا و متن
| «کانال روبیکا»، هر دو به کانال وصل.
|
| سه چیز اینجا قفل می‌شود، چون هر سه می‌توانند بی‌صدا خراب شوند:
|
|  ۱. خودِ لینک و آدرسش - فوتر در هر صفحه‌ای هست، پس اگر آدرس عوض
|     شود هیچ خطایی نمی‌دهد، فقط کاربر جای دیگری می‌رود.
|
|  ۲. rel="noopener noreferrer" - با target="_blank" و بدون آن،
|     صفحه‌ی مقصد از راه window.opener به این صفحه دسترسی دارد.
|
|  ۳. فایل لوگو - اگر در پکیج استقرار جا بیفتد، HTML درست می‌ماند و
|     مرورگر فقط یک تصویر شکسته نشان می‌دهد.
|
*/
class RubikaChannelTest extends TestCase
{
    private const URL = 'https://rubika.ir/sazmat_website';

    private function footer(): string
    {
        return file_get_contents(resource_path('views/layouts/app.blade.php'));
    }

    public function test_the_channel_link_is_in_the_footer(): void
    {
        $html = $this->get(route('contact'))->assertOk()->getContent();

        $this->assertStringContainsString(self::URL, $html, 'لینک کانال روبیکا در فوتر نیست.');
        $this->assertStringContainsString('کانال روبیکا', $html);
    }

    /*
    | و زیر شماره‌ی تلفن بنشیند، نه جای دیگر - کاربر همان‌جا
    | خواستش.
    */
    public function test_it_sits_under_the_phone_number(): void
    {
        $html = $this->get(route('contact'))->assertOk()->getContent();

        $phone = strpos($html, '09134451542');
        $rubika = strpos($html, self::URL);

        $this->assertNotFalse($phone);
        $this->assertNotFalse($rubika);
        $this->assertGreaterThan($phone, $rubika, 'سطر روبیکا باید بعد از شماره‌ی تلفن بیاید.');
    }

    /*
    | ⚠️ هم متن و هم لوگو داخل یک <a> - یعنی کل سطر قابل کلیک
    | است. اگر روزی لوگو از لینک بیرون بیفتد، نصف سطر مُرده
    | می‌شود بی‌آنکه چیزی خطا بدهد.
    */
    public function test_both_the_logo_and_the_text_are_inside_the_link(): void
    {
        $this->assertMatchesRegularExpression(
            '#<a[^>]+href="' . preg_quote(self::URL, '#') . '".*?rubika-logo\.png.*?کانال روبیکا.*?</a>#s',
            $this->footer(),
            'لوگو و متن باید هر دو داخل همان <a> باشند.'
        );
    }

    public function test_the_new_tab_cannot_reach_back_into_this_page(): void
    {
        $this->assertMatchesRegularExpression(
            '#<a[^>]+href="' . preg_quote(self::URL, '#') . '"[^>]*rel="noopener noreferrer"#',
            $this->footer(),
            'با target="_blank" باید rel="noopener noreferrer" هم باشد.'
        );
    }

    /* فایل لوگو واقعاً در مخزن باشد، نه فقط در HTML. */
    public function test_the_logo_file_ships_with_the_site(): void
    {
        $path = base_path('../public_html/images/rubika-logo.png');

        $this->assertFileExists($path, 'فایل لوگوی روبیکا در public_html/images نیست.');

        [$w, $h] = getimagesize($path);

        $this->assertSame(64, $w);
        $this->assertSame(64, $h);
    }

    /*
    |--------------------------------------------------------------------------
    | و قاعده‌ی CSS که سطر را یک‌خطی نگه می‌دارد
    |--------------------------------------------------------------------------
    |
    | ⚠️ این را در کرومیوم اندازه گرفتم، حدس نیست: بدون
    | display:flex روی <a>، لوگو و متن دو خط می‌شوند - ارتفاع سطر
    | از ۲۴ به ۴۲ پیکسل می‌رود و متن از لبه‌ی ستون بیرون می‌زند.
    |
    | چون width و height روی خودِ <img> هم هست، نبودِ این قاعده
    | تصویر را بزرگ نمی‌کند و خرابی فقط در چیدمان دیده می‌شود -
    | یعنی دقیقاً همان چیزی که از نگاه کردن به HTML معلوم نمی‌شود.
    */
    public function test_the_row_stays_on_one_line(): void
    {
        $css = file_get_contents(base_path('../public_html/css/sazmat-theme.css'));

        $this->assertMatchesRegularExpression(
            '/\.footer-contact__channel \{[^}]*display:\s*flex[^}]*align-items:\s*center/s',
            $css,
            'سطر کانال باید flex و هم‌تراز باشد وگرنه دو خط می‌شود.'
        );

        $this->assertMatchesRegularExpression(
            '/\.footer-contact__channel img \{[^}]*width:\s*18px/s',
            $css,
            'لوگو باید هم‌اندازه‌ی آیکون‌های mail و phone باشد.'
        );
    }
}
