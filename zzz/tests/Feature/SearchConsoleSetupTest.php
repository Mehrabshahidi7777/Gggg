<?php

namespace Tests\Feature;

use App\Models\Ad;
use App\Models\Category;
use App\Models\City;
use App\Models\Province;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| آماده‌بودن سایت برای گوگل سرچ کنسول
|--------------------------------------------------------------------------
|
| سه چیز باید درست باشد وگرنه ثبت سایت در سرچ کنسول یا شکست می‌خورد یا
| بی‌فایده می‌ماند:
|
|   ۱. تگ تأیید مالکیت، دقیقاً وقتی تنظیم شده باشد، در <head> بیاید -
|      و وقتی تنظیم نشده، اصلاً چاپ نشود.
|   ۲. آدرس‌های داخل sitemap.xml مطلق و هم‌پروتکلِ دامنه‌ی ثبت‌شده
|      باشند.
|   ۳. robots.txt راه خزنده را به sitemap باز بگذارد.
|
*/
class SearchConsoleSetupTest extends TestCase
{
    use RefreshDatabase;

    /*
    |--------------------------------------------------------------------------
    | تگ تأیید مالکیت
    |--------------------------------------------------------------------------
    */
    public function test_no_verification_tag_is_printed_when_the_code_is_not_set(): void
    {
        config(['seo.google_site_verification' => null]);

        $this->get('/')
            ->assertOk()
            ->assertDontSee('google-site-verification', false);
    }

    public function test_verification_tag_is_printed_when_the_code_is_set(): void
    {
        config(['seo.google_site_verification' => 'abc123-verification-token']);

        $this->get('/')
            ->assertOk()
            ->assertSee(
                '<meta name="google-site-verification" content="abc123-verification-token">',
                false
            );
    }

    /*
    | گوگل تگ را روی هر صفحه‌ای که برای تأیید انتخاب کنیم می‌خواند، نه
    | فقط صفحه‌ی اول. چون تگ در layout است باید همه‌جا باشد.
    */
    public function test_verification_tag_is_present_on_inner_pages_too(): void
    {
        config(['seo.google_site_verification' => 'token-on-every-page']);

        foreach (['/products', '/services'] as $path) {
            $this->get($path)
                ->assertOk()
                ->assertSee('content="token-on-every-page"', false);
        }
    }

    /*
    | کدی که گوگل می‌دهد گاهی شامل کاراکترهای - و _ است. نباید در HTML
    | escape شود و نباید تگ را بشکند.
    */
    public function test_verification_code_is_escaped_and_cannot_break_out_of_the_tag(): void
    {
        config(['seo.google_site_verification' => 'a"><script>alert(1)</script>']);

        $this->get('/')
            ->assertOk()
            ->assertDontSee('<script>alert(1)</script>', false);
    }

    /*
    |--------------------------------------------------------------------------
    | نقشه‌ی سایت
    |--------------------------------------------------------------------------
    */
    public function test_sitemap_is_served_as_xml(): void
    {
        Cache::forget('sitemap.xml');

        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
            ->assertSee('<urlset', false);
    }

    /*
    | گوگل آدرس نسبی را در sitemap قبول نمی‌کند؛ هر <loc> باید مطلق
    | باشد و زیرِ همان دامنه‌ای که در سرچ کنسول ثبت شده.
    */
    public function test_every_sitemap_url_is_absolute_and_on_the_site_domain(): void
    {
        $this->seedApprovedAd();
        Cache::forget('sitemap.xml');

        $xml = $this->get('/sitemap.xml')->assertOk()->getContent();

        preg_match_all('#<loc>(.*?)</loc>#', $xml, $matches);

        $this->assertNotEmpty($matches[1], 'sitemap هیچ آدرسی ندارد.');

        $root = rtrim(config('app.url'), '/');

        foreach ($matches[1] as $loc) {
            $this->assertStringStartsWith(
                $root,
                $loc,
                "آدرس {$loc} مطلق نیست یا روی دامنه‌ی سایت نیست."
            );
        }
    }

    /*
    | اگر روزی آگهیِ تعلیق‌شده یا در انتظار تأیید وارد sitemap شود،
    | گوگل برای آن ۴۰۴ می‌گیرد و در سرچ کنسول به‌عنوان خطا ثبت می‌شود.
    */
    public function test_sitemap_lists_only_publicly_visible_ads(): void
    {
        $visible = $this->seedApprovedAd('آگهی-قابل-دیدن');

        $hidden = $this->seedApprovedAd('آگهی-پنهان');
        $hidden->update(['status' => 'pending']);

        Cache::forget('sitemap.xml');

        // اسلاگ فارسی در آدرس به شکل درصدی کدگذاری می‌شود، پس خودِ
        // آدرس نهایی مقایسه می‌شود نه متن خام اسلاگ.
        $xml = $this->get('/sitemap.xml')->assertOk()->getContent();

        $this->assertStringContainsString(route('ad.show', $visible->slug), $xml);
        $this->assertStringNotContainsString(route('ad.show', $hidden->slug), $xml);
    }

    /*
    |--------------------------------------------------------------------------
    | robots.txt
    |--------------------------------------------------------------------------
    |
    | این فایل استاتیک است و در public_html می‌نشیند، پس با درخواست HTTP
    | آزمایش نمی‌شود؛ خودِ فایل خوانده می‌شود.
    */
    public function test_robots_txt_points_to_the_sitemap_and_does_not_block_the_site(): void
    {
        $robots = file_get_contents(base_path('../public_html/robots.txt'));

        $this->assertStringContainsString('Sitemap: https://sazmat.com/sitemap.xml', $robots);
        $this->assertStringContainsString('Allow: /', $robots);

        // «Disallow: /» به‌تنهایی یعنی کل سایت از گوگل حذف شود.
        $this->assertDoesNotMatchRegularExpression('/^Disallow:\s*\/\s*$/m', $robots);
    }

    /*
    |--------------------------------------------------------------------------
    | کمکی
    |--------------------------------------------------------------------------
    */
    private function seedApprovedAd(string $title = 'آگهی نمونه'): Ad
    {
        $user = User::create([
            'name' => 'فروشنده',
            'username' => 'seller-' . uniqid(),
            'mobile' => '0912' . random_int(1000000, 9999999),
            'password' => 'secret-password',
        ]);

        $province = Province::firstOrCreate(['slug' => 'tehran'], ['name' => 'تهران']);
        $city = City::firstOrCreate(
            ['slug' => 'tehran'],
            ['name' => 'تهران', 'province_id' => $province->id]
        );
        $category = Category::firstOrCreate(
            ['slug' => 'masaleh'],
            ['name' => 'مصالح', 'type' => 'product', 'is_active' => true]
        );

        return Ad::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'province_id' => $province->id,
            'city_id' => $city->id,
            'type' => 'product',
            'title' => $title,
            'price' => 1000,
            'address' => 'آدرس',
            'phone' => '09121234567',
            'status' => 'approved',
        ]);
    }
}
