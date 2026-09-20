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

/*
|--------------------------------------------------------------------------
| وزن‌دهی امتیاز بر اساس سن حساب
|--------------------------------------------------------------------------
|
| ساختن حساب تازه رایگان و آنی است، پس بدون محافظ، خراب‌کردنِ رقیب یا
| بالا بردن آگهی خود فقط چند حساب فاصله دارد.
|
| میانگین حالا وزنی است: SUM(rating * weight) / SUM(weight). وزن در
| لحظه‌ی رأی بر اساس سن حساب ثبت می‌شود.
|
| ⚠️ این تقلب را گران می‌کند، نه غیرممکن: حسابی که دو ماه نگه داشته
| شود وزن کامل می‌گیرد.
|
*/
class RatingWeightTest extends TestCase
{
    use RefreshDatabase;

    private Ad $ad;
    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = $this->userAgedDays(400, 'owner');

        $province = Province::create(['name' => 'تهران', 'slug' => 'tehran']);
        $city = City::create(['name' => 'تهران', 'slug' => 'tehran', 'province_id' => $province->id]);
        $category = Category::create(['name' => 'مصالح', 'slug' => 'masaleh', 'type' => 'product', 'is_active' => true]);

        $this->ad = Ad::create([
            'user_id' => $this->owner->id,
            'category_id' => $category->id,
            'province_id' => $province->id,
            'city_id' => $city->id,
            'type' => 'product',
            'title' => 'آگهی تست',
            'price' => 1000,
            'address' => 'آدرس',
            'phone' => '09121234567',
            'status' => 'approved',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | خودِ جدول وزن‌ها
    |--------------------------------------------------------------------------
    */
    public function test_weight_rises_with_account_age(): void
    {
        $this->assertSame(0.25, AdRating::weightFor($this->userAgedDays(0, 'a')));
        $this->assertSame(0.50, AdRating::weightFor($this->userAgedDays(5, 'b')));
        $this->assertSame(0.75, AdRating::weightFor($this->userAgedDays(20, 'c')));
        $this->assertSame(1.00, AdRating::weightFor($this->userAgedDays(90, 'd')));
    }

    public function test_the_boundaries_land_on_the_higher_tier(): void
    {
        $this->assertSame(0.50, AdRating::weightFor($this->userAgedDays(3, 'e')));
        $this->assertSame(0.75, AdRating::weightFor($this->userAgedDays(14, 'f')));
        $this->assertSame(1.00, AdRating::weightFor($this->userAgedDays(60, 'g')));
    }

    /*
    | اگر جدول تنظیمات خالی یا خراب باشد نباید وزن صفر بدهد، وگرنه
    | یک اشتباه تایپی در کانفیگ همه‌ی امتیازهای سایت را صفر می‌کند.
    */
    public function test_a_broken_config_falls_back_to_full_weight(): void
    {
        config(['marketplace.rating_weights' => []]);

        $this->assertSame(1.0, AdRating::weightFor($this->userAgedDays(0, 'h')));
    }

    public function test_the_feature_can_be_switched_off_from_config(): void
    {
        config(['marketplace.rating_weights' => [0 => 1.0]]);

        $this->assertSame(1.0, AdRating::weightFor($this->userAgedDays(0, 'i')));
    }

    /*
    |--------------------------------------------------------------------------
    | ثبت وزن هنگام امتیازدادن
    |--------------------------------------------------------------------------
    */
    public function test_a_brand_new_account_gets_its_rating_stored_with_a_low_weight(): void
    {
        $newcomer = $this->userAgedDays(0, 'newcomer');

        $this->actingAs($newcomer)
            ->postJson(route('ad.rate', $this->ad), ['rating' => 5])
            ->assertOk();

        $this->assertSame(0.25, AdRating::first()->weight);
    }

    public function test_an_established_account_gets_full_weight(): void
    {
        $regular = $this->userAgedDays(200, 'regular');

        $this->actingAs($regular)->postJson(route('ad.rate', $this->ad), ['rating' => 5]);

        $this->assertSame(1.0, AdRating::first()->weight);
    }

    /*
    |--------------------------------------------------------------------------
    | اثر روی میانگین
    |--------------------------------------------------------------------------
    */
    public function test_the_average_is_weighted_not_plain(): void
    {
        // حساب قدیمی: ۱ ستاره، وزن ۱.۰
        $this->rate($this->userAgedDays(200, 'old'), 1);

        // حساب تازه: ۵ ستاره، وزن ۰.۲۵
        $this->rate($this->userAgedDays(0, 'new'), 5);

        // میانگین ساده ۳.۰ بود. وزنی: (1*1 + 5*0.25) / 1.25 = 1.8
        $loaded = Ad::withRatingSummary()->find($this->ad->id);

        $this->assertSame(1.8, $loaded->rating_average);
    }

    /*
    | تعدادِ نمایش‌داده‌شده باید تعداد واقعی آدم‌ها باشد، نه جمع وزن‌ها.
    | «از ۱.۲۵ امتیاز» برای کاربر بی‌معنی است.
    */
    public function test_the_displayed_count_stays_a_head_count(): void
    {
        $this->rate($this->userAgedDays(200, 'old2'), 1);
        $this->rate($this->userAgedDays(0, 'new2'), 5);

        $this->assertSame(2, Ad::withRatingSummary()->find($this->ad->id)->rating_count);
    }

    /*
    | همان حمله‌ای که این قابلیت برایش ساخته شد: چند حساب تازه در
    | برابر یک حساب قدیمی.
    */
    public function test_a_burst_of_fresh_accounts_moves_the_average_less_than_before(): void
    {
        $this->rate($this->userAgedDays(300, 'honest'), 5);

        foreach (range(1, 4) as $i) {
            $this->rate($this->userAgedDays(0, "sock-{$i}"), 1);
        }

        $loaded = Ad::withRatingSummary()->find($this->ad->id);

        // میانگین ساده: (5 + 1+1+1+1) / 5 = 1.8
        // وزنی: (5*1 + 4*(1*0.25)) / (1 + 4*0.25) = 6 / 2 = 3.0
        $this->assertSame(3.0, $loaded->rating_average);
    }

    public function test_an_ad_with_no_ratings_still_reports_null(): void
    {
        $loaded = Ad::withRatingSummary()->find($this->ad->id);

        $this->assertNull($loaded->rating_average);
        $this->assertSame(0, $loaded->rating_count);
    }

    /*
    | مسیر تنبل: وقتی scope صدا زده نشده، باید همان عدد دربیاید -
    | وگرنه کارت و صفحه‌ی آگهی دو عدد متفاوت نشان می‌دهند.
    */
    public function test_the_lazy_path_agrees_with_the_scope(): void
    {
        $this->rate($this->userAgedDays(200, 'x'), 1);
        $this->rate($this->userAgedDays(0, 'y'), 5);

        $this->assertSame(
            Ad::withRatingSummary()->find($this->ad->id)->rating_average,
            Ad::find($this->ad->id)->rating_average
        );
    }

    /*
    | امتیازهایی که پیش از این تغییر ثبت شده‌اند وزن پیش‌فرض ۱.۰۰
    | می‌گیرند و نباید عقب‌گرد جریمه شوند.
    */
    public function test_rows_written_without_a_weight_default_to_full(): void
    {
        AdRating::create([
            'ad_id' => $this->ad->id,
            'user_id' => $this->userAgedDays(0, 'legacy')->id,
            'rating' => 4,
        ]);

        $this->assertSame(1.0, AdRating::first()->weight);
        $this->assertSame(4.0, Ad::withRatingSummary()->find($this->ad->id)->rating_average);
    }

    /*
    | مرتب‌سازی «بالاترین امتیاز» هم باید از همین میانگین وزنی
    | استفاده کند، نه از ستونی که دیگر وجود ندارد.
    */
    public function test_the_top_rated_sort_uses_the_weighted_average(): void
    {
        $this->get(route('products', ['sort' => 'top_rated']))->assertOk();
    }

    /*
    |--------------------------------------------------------------------------
    | کمکی
    |--------------------------------------------------------------------------
    */
    private function userAgedDays(int $days, string $handle): User
    {
        $user = User::create([
            'name' => $handle,
            'username' => $handle,
            'mobile' => '0912' . str_pad((string) random_int(0, 9999999), 7, '0', STR_PAD_LEFT),
            'password' => 'secret-password',
        ]);

        $user->forceFill(['created_at' => now()->subDays($days)])->save();

        return $user->fresh();
    }

    private function rate(User $user, int $stars): void
    {
        $this->actingAs($user)
            ->postJson(route('ad.rate', $this->ad), ['rating' => $stars])
            ->assertOk();
    }
}
