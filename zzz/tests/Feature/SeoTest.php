<?php

namespace Tests\Feature;

use App\Models\Ad;
use App\Models\AdRating;
use App\Models\Category;
use App\Models\City;
use App\Models\Province;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoTest extends TestCase
{
    use RefreshDatabase;

    private function makeAd(string $title, string $type = 'product'): Ad
    {
        $user = User::create([
            'name' => 'ف', 'username' => 'u' . Ad::count() . $type,
            'mobile' => '0912000' . str_pad((string) Ad::count(), 4, '0', STR_PAD_LEFT),
            'password' => 'secret-password',
        ]);

        $province = Province::firstOrCreate(['slug' => 'tehran'], ['name' => 'تهران']);
        $city = City::firstOrCreate(
            ['province_id' => $province->id, 'slug' => 'tehran'],
            ['name' => 'تهران']
        );
        $category = Category::firstOrCreate(
            ['slug' => 'cat-' . $type],
            ['name' => 'مصالح پایه', 'type' => $type, 'is_active' => true]
        );

        return Ad::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'province_id' => $province->id,
            'city_id' => $city->id,
            'type' => $type,
            'title' => $title,
            'description' => 'سیمان تیپ ۲ با کیفیت عالی و قیمت مناسب برای پروژه‌های ساختمانی.',
            'price' => 250000,
            'address' => 'آدرس',
            'phone' => '09121234567',
            'status' => 'approved',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | اسلاگ فارسی
    |--------------------------------------------------------------------------
    */
    public function test_persian_title_produces_a_readable_persian_slug(): void
    {
        $ad = $this->makeAd('سیمان تیپ ۲ اصفهان');

        $this->assertSame('سیمان-تیپ-2-اصفهان', $ad->slug);
    }

    /*
    | آدرس صفحه نباید با ویرایش عنوان عوض شود، وگرنه هر لینکی که
    | قبلاً به این آگهی داده شده ۴۰۴ می‌شود.
    */
    public function test_slug_does_not_change_when_the_title_is_edited(): void
    {
        $ad = $this->makeAd('سیمان تیپ ۲ اصفهان');
        $original = $ad->slug;

        $ad->update(['title' => 'عنوان کاملاً متفاوت']);

        $this->assertSame($original, $ad->fresh()->slug);
    }

    public function test_emoji_only_title_still_gets_a_usable_slug(): void
    {
        $ad = $this->makeAd('🏗️🧱');

        $this->assertNotSame('', $ad->slug);
        $this->assertStringStartsWith('ad-', $ad->slug);

        $this->get(route('ad.show', $ad->slug))->assertOk();
    }

    /*
    |--------------------------------------------------------------------------
    | متا و canonical
    |--------------------------------------------------------------------------
    */
    public function test_ad_page_has_its_own_meta_description(): void
    {
        $ad = $this->makeAd('سیمان تیپ ۲');

        $html = $this->get(route('ad.show', $ad->slug))->assertOk()->getContent();

        $this->assertMatchesRegularExpression(
            '/<meta name="description" content="[^"]*سیمان[^"]*"/u',
            $html
        );
    }

    public function test_listing_canonical_ignores_filters_and_paging(): void
    {
        $html = $this->get('/products?page=3&sort=popular&province=1')
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString(
            '<link rel="canonical" href="' . route('products') . '">',
            $html
        );
    }

    /*
    |--------------------------------------------------------------------------
    | ...ولی دسته استثناست
    |--------------------------------------------------------------------------
    |
    | ⚠️ این تست از یک تناقض واقعی بین نقشه‌ی سایت و خودِ صفحه آمده.
    |
    | نقشه‌ی سایت آدرس هر دسته را جداگانه به گوگل می‌دهد
    | (/products?category=5)، ولی canonical همان صفحه آدرسِ بی‌فیلتر
    | بود. یعنی نقشه می‌گفت «این را ببین» و صفحه می‌گفت «نه، من
    | تکراری‌ام».
    |
    | در سرچ کنسول نتیجه‌اش این است که همه‌ی صفحه‌های دسته زیر عنوان
    | «Alternate page with proper canonical tag» کنار گذاشته می‌شوند.
    | یعنی کل دسته‌بندی‌ها از گوگل بیرون می‌مانند - بی‌آنکه هیچ خطایی
    | جایی دیده شود.
    */
    public function test_a_category_page_points_at_itself(): void
    {
        $ad = $this->makeAd('سیمان تیپ ۲');

        $html = $this->get('/products?category=' . $ad->category_id . '&page=2&sort=new')
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString(
            '<link rel="canonical" href="' . route('products', ['category' => $ad->category_id]) . '">',
            $html,
            'صفحه‌ی دسته باید خودش را canonical معرفی کند، وگرنه از نتایج گوگل بیرون می‌ماند.'
        );
    }

    /*
    | و همان آدرسی باشد که در نقشه‌ی سایت آمده - نه یک شکلِ دیگرش.
    |
    | اگر این دو کوچک‌ترین فرقی داشته باشند (مثلاً یکی اسلاگ بدهد و
    | آن یکی شناسه)، گوگل باز هم آنها را دو صفحه می‌بیند و همان
    | مشکلِ اول برمی‌گردد.
    */
    public function test_the_sitemap_and_the_canonical_agree(): void
    {
        $ad = $this->makeAd('سیمان تیپ ۲');

        $xml = simplexml_load_string($this->get('/sitemap.xml')->assertOk()->getContent());

        $locations = [];
        foreach ($xml->url as $url) {
            $locations[] = (string) $url->loc;
        }

        $categoryUrl = route('products', ['category' => $ad->category_id]);

        $this->assertContains($categoryUrl, $locations, 'آدرس دسته در نقشه‌ی سایت نیست.');

        $html = $this->get($categoryUrl)->assertOk()->getContent();

        $this->assertStringContainsString(
            '<link rel="canonical" href="' . $categoryUrl . '">',
            $html,
            'آدرسی که در نقشه هست باید خودش را canonical معرفی کند.'
        );
    }

    /*
    | و شناسه‌ی بی‌معنا نباید آدرس بسازد.
    |
    | با ?category=999 صفحه خالی برمی‌گردد؛ اگر همان را canonical
    | کنیم، به گوگل یک آدرسِ بی‌ارزش معرفی کرده‌ایم که خودمان هرگز
    | جایی لینکش نداده‌ایم.
    */
    public function test_an_unknown_category_falls_back_to_the_plain_listing(): void
    {
        $html = $this->get('/products?category=999999')->assertOk()->getContent();

        $this->assertStringContainsString(
            '<link rel="canonical" href="' . route('products') . '">',
            $html
        );
    }

    /*
    | و دسته‌ی خدمات روی صفحه‌ی محصولات هم همین‌طور - آنجا هم
    | نتیجه خالی است.
    */
    public function test_a_category_of_the_other_type_does_not_become_canonical(): void
    {
        $service = $this->makeAd('نقاشی ساختمان', 'service');

        $html = $this->get('/products?category=' . $service->category_id)
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString(
            '<link rel="canonical" href="' . route('products') . '">',
            $html
        );
    }

    /*
    |--------------------------------------------------------------------------
    | داده‌ی ساخت‌یافته
    |--------------------------------------------------------------------------
    */
    public function test_ad_page_emits_valid_json_ld(): void
    {
        $ad = $this->makeAd('سیمان تیپ ۲');

        $html = $this->get(route('ad.show', $ad->slug))->assertOk()->getContent();

        preg_match_all(
            '~<script type="application/ld\+json">(.*?)</script>~s',
            $html,
            $matches
        );

        $found = null;

        foreach ($matches[1] as $block) {
            $data = json_decode(trim($block), true);
            $this->assertSame(JSON_ERROR_NONE, json_last_error(), 'JSON-LD نامعتبر است');
            if (($data['@type'] ?? null) === 'Product') {
                $found = $data;
            }
        }

        $this->assertNotNull($found, 'schema از نوع Product پیدا نشد');
        $this->assertSame('سیمان تیپ ۲', $found['name']);
        $this->assertSame('2500000', $found['offers']['price'], 'قیمت باید به ریال تبدیل شود');

        // بدون امتیاز، aggregateRating نباید اصلاً فرستاده شود
        $this->assertArrayNotHasKey('aggregateRating', $found);
    }

    public function test_json_ld_includes_star_rating_once_the_ad_is_rated(): void
    {
        $ad = $this->makeAd('سیمان تیپ ۲');

        foreach ([4, 5] as $i => $rating) {
            $rater = User::create([
                'name' => 'r', 'username' => 'rater' . $i,
                'mobile' => '0913000000' . $i, 'password' => 'secret-password',
            ]);
            AdRating::create(['ad_id' => $ad->id, 'user_id' => $rater->id, 'rating' => $rating]);
        }

        $html = $this->get(route('ad.show', $ad->slug))->assertOk()->getContent();

        preg_match('~<script type="application/ld\+json">(.*?)</script>~s', $html, $m);
        $data = json_decode(trim($m[1]), true);

        // ممکن است اولین بلاک، schema سازمان در لایه باشد
        if (($data['@type'] ?? null) !== 'Product') {
            preg_match_all('~<script type="application/ld\+json">(.*?)</script>~s', $html, $all);
            foreach ($all[1] as $b) {
                $d = json_decode(trim($b), true);
                if (($d['@type'] ?? null) === 'Product') { $data = $d; break; }
            }
        }

        $this->assertSame('4.5', $data['aggregateRating']['ratingValue']);
        $this->assertSame(2, $data['aggregateRating']['reviewCount']);
        $this->assertSame('5', $data['aggregateRating']['bestRating']);
    }

    /*
    |--------------------------------------------------------------------------
    | نقشه‌ی سایت
    |--------------------------------------------------------------------------
    */
    public function test_sitemap_is_valid_xml_and_lists_approved_ads(): void
    {
        $visible = $this->makeAd('آگهی قابل نمایش');

        $hidden = $this->makeAd('آگهی تعلیق‌شده', 'service');
        $hidden->update(['is_suspended' => true, 'suspended_at' => now()]);

        $response = $this->get('/sitemap.xml')->assertOk();

        $response->assertHeader('Content-Type', 'application/xml; charset=UTF-8');

        $xml = simplexml_load_string($response->getContent());

        $this->assertNotFalse($xml, 'sitemap یک XML معتبر نیست');

        $locations = [];
        foreach ($xml->url as $url) {
            $locations[] = (string) $url->loc;
        }

        $this->assertContains(route('home'), $locations);
        $this->assertContains(route('ad.show', $visible->slug), $locations);

        // آگهی تعلیق‌شده نباید به گوگل معرفی شود (تحویل ۴۰۴)
        $this->assertNotContains(route('ad.show', $hidden->slug), $locations);
    }
}
