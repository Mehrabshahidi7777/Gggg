<?php

namespace Tests\Feature;

use App\Models\Ad;
use App\Models\Category;
use App\Models\City;
use App\Models\Province;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| آگهی ویژه: سقف در صفحه‌ی اصلی، چرخش، و صفحه‌ی جدا
|--------------------------------------------------------------------------
|
| «ویژه» فقط وقتی ارزش دارد که کمیاب باشد. اگر هر آگهی ویژه‌ای روی
| صفحه‌ی اصلی بیاید، صفحه پر می‌شود و دیده‌شدن در آن بی‌معنی می‌شود -
| یعنی همان چیزی که ارائه‌دهنده پولش را داده از بین می‌رود.
|
| ولی سقف به تنهایی مسئله‌ی بدتری می‌ساخت: با latest()، هر که زودتر
| ویژه می‌شد جایش را می‌گرفت و بقیه هرگز روی صفحه‌ی اصلی دیده
| نمی‌شدند - در حالی که همه یک پول داده‌اند.
|
| پس نقطه‌ی شروعِ برش هر ساعت جلو می‌رود و همه نوبت می‌گیرند، و
| بقیه‌شان همیشه در /featured هستند.
|
| ⚠️ چرخش به زمان بسته است نه به تصادف: رفرش صفحه نباید چیز تازه‌ای
| نشان بدهد، وگرنه کاربر گیج می‌شود و فکر می‌کند آگهی‌ها ناپایدارند.
|
*/
class FeaturedRotationTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::create([
            'name' => 'ارائه‌دهنده', 'username' => 'provider',
            'mobile' => '09120000060', 'password' => 'secret-password',
        ]);

        config(['marketplace.featured_on_home' => 2]);
        config(['marketplace.featured_rotation_seconds' => 3600]);
    }

    /*
    |--------------------------------------------------------------------------
    | سقف
    |--------------------------------------------------------------------------
    */
    public function test_the_home_page_shows_no_more_than_the_cap(): void
    {
        $this->featuredAds('product', 7);

        $this->get(route('home'))
            ->assertOk()
            ->assertViewHas('featuredProducts', fn ($ads) => $ads->count() === 2);
    }

    public function test_fewer_ads_than_the_cap_are_all_shown(): void
    {
        $this->featuredAds('product', 1);

        $this->get(route('home'))
            ->assertOk()
            ->assertViewHas('featuredProducts', fn ($ads) => $ads->count() === 1);
    }

    /*
    |--------------------------------------------------------------------------
    | چرخش
    |--------------------------------------------------------------------------
    */
    public function test_a_different_hour_shows_a_different_set(): void
    {
        $this->featuredAds('product', 6);

        Carbon::setTestNow(Carbon::createFromTimestamp(0));
        $first = $this->homeIds();

        Carbon::setTestNow(Carbon::createFromTimestamp(3600));
        $second = $this->homeIds();

        Carbon::setTestNow();

        $this->assertNotSame($first, $second, 'چرخش اتفاق نیفتاد؛ همان‌ها دوباره آمدند.');
    }

    /*
    | و در همان ساعت، رفرش چیز تازه‌ای نشان نمی‌دهد.
    */
    public function test_refreshing_within_the_same_hour_is_stable(): void
    {
        $this->featuredAds('product', 6);

        Carbon::setTestNow(Carbon::createFromTimestamp(1000));
        $first = $this->homeIds();
        $second = $this->homeIds();
        Carbon::setTestNow();

        $this->assertSame($first, $second);
    }

    /*
    | در یک دور کامل، همه‌ی آگهی‌ها باید حداقل یک بار دیده شوند -
    | وگرنه کسی پول داده و هرگز روی صفحه‌ی اصلی نیامده.
    */
    public function test_every_featured_ad_gets_a_turn_within_one_cycle(): void
    {
        $ads = $this->featuredAds('product', 6);
        $seen = [];

        // ۶ آگهی، سقف ۲ ⇒ سه ساعت برای یک دور کامل
        foreach (range(0, 2) as $hour) {
            Carbon::setTestNow(Carbon::createFromTimestamp($hour * 3600));
            $seen = array_merge($seen, $this->homeIds());
        }

        Carbon::setTestNow();

        $this->assertEqualsCanonicalizing(
            $ads->pluck('id')->all(),
            array_unique($seen),
            'بعضی آگهی‌های ویژه در یک دور کامل هرگز دیده نشدند.'
        );
    }

    /*
    | وقتی برش به ته فهرست می‌رسد، باقی‌اش باید از اول برداشته شود -
    | وگرنه در آن ساعت صفحه‌ی اصلی نصفه می‌ماند.
    */
    public function test_the_wrap_around_never_leaves_the_home_page_short(): void
    {
        $this->featuredAds('product', 5);

        foreach (range(0, 5) as $hour) {
            Carbon::setTestNow(Carbon::createFromTimestamp($hour * 3600));

            $this->assertCount(
                2,
                $this->homeIds(),
                "در ساعت {$hour} صفحه‌ی اصلی کمتر از سقف آگهی داشت."
            );
        }

        Carbon::setTestNow();
    }

    /*
    |--------------------------------------------------------------------------
    | لینک «دیدن همه»
    |--------------------------------------------------------------------------
    */
    public function test_the_link_appears_only_when_something_is_left_out(): void
    {
        $this->featuredAds('product', 5);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('دیدن همه‌ی 5 محصول ویژه', false);
    }

    public function test_the_link_is_hidden_when_everything_already_fits(): void
    {
        $this->featuredAds('product', 2);

        $this->get(route('home'))
            ->assertOk()
            ->assertDontSee('دیدن همه‌ی', false);
    }

    /*
    |--------------------------------------------------------------------------
    | صفحه‌ی همه‌ی ویژه‌ها
    |--------------------------------------------------------------------------
    */
    public function test_the_featured_page_lists_both_kinds(): void
    {
        $this->featuredAds('product', 3);
        $this->featuredAds('service', 2);

        $this->get(route('featured'))
            ->assertOk()
            ->assertViewHas('products', fn ($p) => $p->total() === 3)
            ->assertViewHas('services', fn ($s) => $s->total() === 2);
    }

    /*
    | آگهی‌ای که ویژه نیست نباید اینجا بیاید، و آگهی تأییدنشده هم نه.
    */
    public function test_only_approved_featured_ads_are_listed(): void
    {
        $this->featuredAds('product', 1);
        $this->ad('product', featured: false, approved: true);
        $this->ad('product', featured: true, approved: false);

        $this->get(route('featured'))
            ->assertOk()
            ->assertViewHas('products', fn ($p) => $p->total() === 1);
    }

    /*
    | هر بخش صفحه‌بندی خودش را دارد، وگرنه رفتن به صفحه‌ی دوم
    | محصولات، خدمات را هم جابه‌جا می‌کرد.
    */
    public function test_each_section_paginates_independently(): void
    {
        $this->featuredAds('product', 20);
        $this->featuredAds('service', 3);

        $html = $this->get(route('featured', ['products' => 2]))->assertOk()->getContent();

        $this->assertStringContainsString('products=', $html);

        $this->get(route('featured', ['products' => 2]))
            ->assertOk()
            ->assertViewHas('services', fn ($s) => $s->count() === 3);
    }

    /*
    |--------------------------------------------------------------------------
    | کمکی
    |--------------------------------------------------------------------------
    */
    private function homeIds(): array
    {
        $ads = $this->get(route('home'))->assertOk()->viewData('featuredProducts');

        return $ads->pluck('id')->all();
    }

    private function featuredAds(string $type, int $count)
    {
        return collect(range(1, $count))->map(fn () => $this->ad($type));
    }

    private function ad(string $type, bool $featured = true, bool $approved = true): Ad
    {
        $province = Province::firstOrCreate(['slug' => 'tehran'], ['name' => 'تهران']);
        $city = City::firstOrCreate(
            ['slug' => 'tehran', 'province_id' => $province->id],
            ['name' => 'تهران']
        );
        $category = Category::firstOrCreate(
            ['slug' => 'masaleh-' . $type],
            ['name' => 'دسته', 'type' => $type, 'is_active' => true]
        );

        return Ad::create([
            'user_id' => $this->owner->id,
            'category_id' => $category->id,
            'province_id' => $province->id,
            'city_id' => $city->id,
            'type' => $type,
            'title' => 'آگهی ' . uniqid(),
            'price' => 1000,
            'address' => 'آدرس',
            'phone' => '09121234567',
            'status' => $approved ? 'approved' : 'pending',
            'is_featured' => $featured,
        ]);
    }
}
