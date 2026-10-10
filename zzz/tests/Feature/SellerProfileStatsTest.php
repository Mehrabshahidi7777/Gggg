<?php

namespace Tests\Feature;

use App\Models\Ad;
use App\Models\AdContactReveal;
use App\Models\Category;
use App\Models\City;
use App\Models\Province;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| آمار بالای صفحه‌ی فروشنده
|--------------------------------------------------------------------------
|
| ⚠️ این از چیزی آمده که مهراب روی صفحه‌ی یک فروشنده‌ی تازه دید:
| «۰ قلم تکمیل‌شده».
|
| آن عدد سفارش‌های سبد خرید را می‌شمرد، ولی خرید آنلاین در سایت خاموش
| است. یعنی برای همه‌ی فروشنده‌ها همیشه صفر بود و به خریدار می‌گفت
| «این فروشنده هیچ‌چیز نفروخته» - درست برعکسِ کاری که باید بکند.
|
| جایش «چند نفر شماره را دیده‌اند» آمد: کاری که خریدارِ این سایت
| واقعاً انجام می‌دهد.
|
*/
class SellerProfileStatsTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $mobile): User
    {
        return User::create([
            'name' => 'کاربر', 'username' => 'u' . $mobile,
            'mobile' => $mobile, 'password' => 'secret-password',
        ]);
    }

    private function ad(User $owner): Ad
    {
        $province = Province::firstOrCreate(['slug' => 'tehran'], ['name' => 'تهران']);
        $city = City::firstOrCreate(['province_id' => $province->id, 'slug' => 'tehran'], ['name' => 'تهران']);
        $category = Category::firstOrCreate(
            ['slug' => 'cement'],
            ['name' => 'سیمان', 'type' => 'product', 'is_active' => true]
        );

        return Ad::create([
            'user_id' => $owner->id, 'category_id' => $category->id,
            'province_id' => $province->id, 'city_id' => $city->id,
            'type' => 'product', 'title' => 'سیمان ' . Ad::count(),
            'description' => 'سیمان تیپ ۲ برای پروژه‌های ساختمانی.',
            'price' => 250000, 'address' => 'آدرس', 'phone' => '09121234567',
            'status' => 'approved',
        ]);
    }

    private function reveal(Ad $ad, ?User $viewer, string $day, string $ip = '1.1.1.1'): void
    {
        AdContactReveal::create([
            'ad_id' => $ad->id,
            'user_id' => $viewer?->id,
            'ip_hash' => hash('sha256', $ip),
            'revealed_on' => $day,
        ]);
    }

    private function page(User $seller): string
    {
        return $this->get(route('seller.profile', $seller))->assertOk()->getContent();
    }

    /*
    |--------------------------------------------------------------------------
    | «قلم تکمیل‌شده»
    |--------------------------------------------------------------------------
    */
    public function test_completed_items_are_hidden_while_online_checkout_is_off(): void
    {
        config(['marketplace.online_checkout' => false]);

        $seller = $this->user('09120000001');
        $this->ad($seller);

        $this->assertStringNotContainsString('قلم تکمیل‌شده', $this->page($seller));
    }

    /* و اگر روزی روشن شد، خودش برمی‌گردد. */
    public function test_completed_items_come_back_when_online_checkout_is_on(): void
    {
        config(['marketplace.online_checkout' => true]);

        $seller = $this->user('09120000001');
        $this->ad($seller);

        $this->assertStringContainsString('قلم تکمیل‌شده', $this->page($seller));
    }

    /*
    |--------------------------------------------------------------------------
    | «شمار افراد دیده‌شده» (چند نفر شماره را دیده‌اند)
    |--------------------------------------------------------------------------
    |
    | ⚠️ آدم، نه دفعه. جدول برای هر کاربر روزی یک ردیف نگه می‌دارد؛
    | اگر ردیف می‌شمردیم، کسی که سه روز سر زده سه نفر حساب می‌شد.
    |
    | اینجا: الف دو روز روی یک آگهی و یک روز روی آگهی دیگرِ همین
    | فروشنده، ب یک بار، و یک ردیفِ قدیمیِ بی‌حساب (فقط هش IP).
    | یعنی سه نفر - نه پنج ردیف.
    */
    public function test_it_counts_people_not_rows(): void
    {
        $seller = $this->user('09120000001');
        $first = $this->ad($seller);
        $second = $this->ad($seller);

        $a = $this->user('09120000002');
        $b = $this->user('09120000003');

        $this->reveal($first, $a, '2026-10-01');
        $this->reveal($first, $a, '2026-10-02');
        $this->reveal($second, $a, '2026-10-02');
        $this->reveal($first, $b, '2026-10-02', '2.2.2.2');
        $this->reveal($first, null, '2026-09-10', '3.3.3.3');

        $html = $this->page($seller);

        $this->assertMatchesRegularExpression(
            '#<strong>3</strong><span>شمار افراد دیده‌شده</span>#u',
            $html,
            'باید سه نفر باشد: الف، ب، و ردیف قدیمیِ بی‌حساب.'
        );
    }

    /* شماره‌ی فروشنده‌ی دیگر نباید به این یکی اضافه شود. */
    public function test_another_sellers_reveals_do_not_count(): void
    {
        $seller = $this->user('09120000001');
        $this->ad($seller);

        $other = $this->user('09120000009');
        $otherAd = $this->ad($other);

        $this->reveal($otherAd, $this->user('09120000002'), '2026-10-01');

        $this->assertStringNotContainsString('شمار افراد دیده‌شده', $this->page($seller));
    }

    /*
    | و صفر نشان داده نمی‌شود - همان مشکلِ «۰ قلم تکمیل‌شده» از نو
    | بود: فروشنده‌ی تازه‌وارد را بی‌مشتری نشان می‌داد.
    */
    public function test_zero_is_not_shown(): void
    {
        $seller = $this->user('09120000001');
        $this->ad($seller);

        $html = $this->page($seller);

        $this->assertStringNotContainsString('شمار افراد دیده‌شده', $html);
        $this->assertStringContainsString('آگهی فعال', $html);
    }
}
