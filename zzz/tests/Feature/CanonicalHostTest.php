<?php

namespace Tests\Feature;

use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| یک میزبانِ متعارف: www.sazmat.com
|--------------------------------------------------------------------------
|
| ⚠️ این تست از یک ناهماهنگی واقعی آمده.
|
| سایت روی www.sazmat.com بالا می‌آید (نوار آدرس مرورگر همین را
| نشان می‌دهد)، ولی robots.txt نقشه‌ی سایت را روی sazmat.com معرفی
| می‌کرد - بدونِ www.
|
| برای گوگل اینها دو سایتِ جدا با محتوای یکسان‌اند:
|
|   - اعتبار هر صفحه بین دو آدرس پخش می‌شود،
|   - در سرچ کنسول نیمی از صفحه‌ها «Duplicate» می‌خورند،
|   - و اگر property روی یکی ثبت شده باشد، آدرس‌های آن یکی بیرونِ
|     دامنه‌ی تأییدشده دیده می‌شوند.
|
| هیچ‌کدامِ اینها خطا نمی‌دهند. فقط نتیجه نمی‌گیری.
|
| سه جا باید یک چیز بگویند و این تست هر سه را کنار هم می‌گذارد:
|
|   ۱. robots.txt   - نقشه را کجا معرفی می‌کند
|   ۲. .htaccess    - بدونِ www را به کجا می‌فرستد
|   ۳. .env         - APP_URL، که sitemap و canonical از آن ساخته
|                     می‌شوند (این یکی روی هاست است و اینجا دیده
|                     نمی‌شود؛ در README کنارش نوشته شده)
|
*/
class CanonicalHostTest extends TestCase
{
    private const HOST = 'www.sazmat.com';

    private function file(string $name): string
    {
        $path = base_path('../public_html/' . $name);

        $this->assertFileExists($path, "فایل {$name} پیدا نشد.");

        return file_get_contents($path);
    }

    public function test_robots_points_the_sitemap_at_the_canonical_host(): void
    {
        preg_match('/^Sitemap:\s*(\S+)$/m', $this->file('robots.txt'), $m);

        $this->assertNotEmpty($m, 'robots.txt خطِ Sitemap ندارد.');

        $this->assertSame(
            'https://' . self::HOST . '/sitemap.xml',
            $m[1],
            'آدرس نقشه در robots.txt باید همان میزبانی باشد که سایت روی آن بالا می‌آید.'
        );
    }

    public function test_the_bare_domain_is_redirected_to_the_canonical_host(): void
    {
        $htaccess = $this->file('.htaccess');

        $this->assertMatchesRegularExpression(
            '/RewriteCond %\{HTTP_HOST\} \^sazmat\\\\\.com\$ \[NC\]/',
            $htaccess,
            'بدونِ www باید به www منتقل شود.'
        );

        $this->assertMatchesRegularExpression(
            '#RewriteRule \^ https://' . preg_quote(self::HOST, '#') . '%\{REQUEST_URI\} \[L,R=301\]#',
            $htaccess,
            'انتقال باید دائمی (۳۰۱) و به میزبانِ متعارف باشد.'
        );
    }

    /*
    | ⚠️ شرطِ انتقال باید دقیقاً sazmat.com باشد، نه «هر چیزی که www
    | ندارد».
    |
    | با شرطِ کلی، مقصدِ انتقال خودش هم شرط را برآورده می‌کند و سایت
    | در حلقه‌ی بی‌نهایتِ انتقال می‌افتد - یعنی کل سایت از دسترس
    | خارج می‌شود، نه فقط سئو.
    */
    public function test_the_redirect_cannot_loop(): void
    {
        $htaccess = $this->file('.htaccess');

        $this->assertStringNotContainsString(
            '!^www\.',
            $htaccess,
            'شرطِ «هر چیزی که www ندارد» می‌تواند حلقه بسازد؛ شرط باید دقیقاً sazmat.com باشد.'
        );
    }

    /*
    | و انتقال باید پیش از رساندنِ درخواست به لاراول بیاید، وگرنه
    | هرگز اجرا نمی‌شود.
    */
    public function test_the_redirect_comes_before_the_front_controller(): void
    {
        $htaccess = $this->file('.htaccess');

        $redirect = strpos($htaccess, 'https://' . self::HOST);
        $front = strpos($htaccess, 'index.php');

        $this->assertNotFalse($redirect);
        $this->assertNotFalse($front);

        $this->assertLessThan(
            $front,
            $redirect,
            'قاعده‌ی انتقال باید بالاتر از قاعده‌ی index.php بنشیند.'
        );
    }
}
