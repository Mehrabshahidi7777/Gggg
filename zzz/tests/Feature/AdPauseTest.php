<?php

namespace Tests\Feature;

use App\Models\Ad;
use App\Models\AdRating;
use App\Models\Category;
use App\Models\City;
use App\Models\Province;
use App\Models\ServicePlan;
use App\Models\ServiceSubscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| خاموش و روشن کردن موقتِ آگهی
|--------------------------------------------------------------------------
|
| تا امروز تنها راهِ پنهان‌کردن یک آگهی، حذفش بود. کسی که جنسش تمام
| شده یا چند هفته سرش شلوغ است، مجبور بود آگهی را پاک کند و بعد از نو
| بسازد - یعنی عکس‌ها، امتیازها و نظرهایش را برای همیشه از دست بدهد.
|
| ⚠️ ستون جدا از is_suspended، و این مهم‌ترین نکته‌ی این تغییر است:
|
|   is_suspended : سیستم خاموش کرده (اشتراک تمام شده)
|   paused_at    : خودِ صاحب آگهی خاموش کرده
|
| اگر روی یک ستون می‌نشستند، اولین اجرای کرون تصمیم کاربر را پاک
| می‌کرد - یا بدتر، آگهیِ تعلیق‌شده به‌خاطر نپرداختن را «فعال» می‌کرد.
|
*/
class AdPauseTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;
    private Ad $ad;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = $this->user('owner', '09120000070');

        $plan = ServicePlan::create([
            'type' => 'product', 'months' => 1,
            'title' => 'یک ماهه', 'price' => 150000, 'is_active' => true,
        ]);

        ServiceSubscription::create([
            'type' => 'product',
            'user_id' => $this->owner->id,
            'service_plan_id' => $plan->id,
            'amount' => 150000,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addMonth(),
            'paid_at' => now()->subDay(),
            'status' => 'active',
        ]);

        $this->ad = $this->makeAd();
    }

    /*
    |--------------------------------------------------------------------------
    | خاموش‌کردن، همه‌جا
    |--------------------------------------------------------------------------
    |
    | شرط در scopeApproved نشسته، که تنها دروازه‌ی سایت است. این تست‌ها
    | مطمئن می‌شوند واقعاً همه‌ی مسیرها از آن رد می‌شوند.
    */
    public static function publicPages(): array
    {
        return [
            'صفحه‌ی اصلی' => ['home'],
            'فهرست محصولات' => ['products'],
        ];
    }

    #[DataProvider('publicPages')]
    public function test_a_paused_ad_disappears_from_the_public_site(string $route): void
    {
        $this->get(route($route))->assertOk()->assertSee('سیمان تیپ دو');

        $this->pause();

        $this->get(route($route))->assertOk()->assertDontSee('سیمان تیپ دو');
    }

    /*
    | نقشه‌ی سایت آدرس دارد نه عنوان، پس جداگانه بررسی می‌شود. مهم
    | است چون گوگل از همین فایل آدرس‌ها را برمی‌دارد؛ ماندنِ یک آگهی
    | خاموش در آن یعنی گوگل مدام سراغ صفحه‌ای می‌رود که ۴۰۴ می‌دهد.
    */
    public function test_a_paused_ad_leaves_the_sitemap(): void
    {
        $url = route('ad.show', $this->ad->slug);

        $this->get(route('sitemap'))->assertOk()->assertSee($url, false);

        $this->pause();

        $this->get(route('sitemap'))->assertOk()->assertDontSee($url, false);
    }

    public function test_the_ad_page_itself_becomes_unavailable(): void
    {
        $this->get(route('ad.show', $this->ad->slug))->assertOk();

        $this->pause();

        $this->get(route('ad.show', $this->ad->slug))->assertNotFound();
    }

    public function test_a_paused_ad_is_out_of_search_results(): void
    {
        $this->pause();

        $this->get(route('search', ['search' => 'سیمان']))
            ->assertOk()
            ->assertDontSee('سیمان تیپ دو');
    }

    /*
    |--------------------------------------------------------------------------
    | روشن‌کردن دوباره
    |--------------------------------------------------------------------------
    */
    public function test_resuming_brings_the_ad_back_untouched(): void
    {
        $rater = $this->user('rater', '09120000071');

        AdRating::create([
            'ad_id' => $this->ad->id,
            'user_id' => $rater->id,
            'rating' => 5,
        ]);

        $this->pause();
        $this->resume();

        $this->get(route('ad.show', $this->ad->slug))->assertOk();

        /*
        | مهم‌ترین تفاوت با حذف‌کردن: چیزی از دست نمی‌رود.
        */
        $this->assertSame(1, AdRating::where('ad_id', $this->ad->id)->count());
        $this->assertNull($this->ad->fresh()->paused_at);
    }

    /*
    | دو بار زدنِ دکمه نباید تاریخ خاموش‌شدن را عقب‌وجلو کند - مثلاً
    | وقتی کاربر صفحه را رفرش می‌کند.
    */
    public function test_pausing_twice_does_not_move_the_timestamp(): void
    {
        $this->pause();
        $first = $this->ad->fresh()->paused_at;

        $this->travel(5)->minutes();
        $this->pause();

        $this->assertEquals($first, $this->ad->fresh()->paused_at);
    }

    /*
    |--------------------------------------------------------------------------
    | مرز با تعلیقِ سیستمی
    |--------------------------------------------------------------------------
    |
    | همان چیزی که این ستونِ جدا برایش ساخته شد.
    */
    public function test_resuming_does_not_undo_a_system_suspension(): void
    {
        $this->ad->forceFill([
            'is_suspended' => true,
            'suspended_at' => now(),
        ])->save();

        $this->pause();
        $this->resume();

        $this->assertTrue($this->ad->fresh()->is_suspended, 'تعلیق سیستمی برداشته شد.');
        $this->get(route('ad.show', $this->ad->slug))->assertNotFound();
    }

    /*
    | و کاربر باید بداند روشن‌کردن کافی نبوده، وگرنه دنبال آگهی‌اش
    | در سایت می‌گردد و پیدا نمی‌کند.
    */
    public function test_the_user_is_told_when_resuming_is_not_enough(): void
    {
        $this->ad->forceFill(['is_suspended' => true, 'suspended_at' => now()])->save();

        $this->pause();

        $this->actingAs($this->owner)
            ->post(route('ad.resume', $this->ad))
            ->assertSessionHas('success', fn ($m) => str_contains($m, 'تمدید اشتراک'));
    }

    /*
    |--------------------------------------------------------------------------
    | مالکیت
    |--------------------------------------------------------------------------
    */
    public function test_a_stranger_cannot_pause_someone_elses_ad(): void
    {
        $stranger = $this->user('stranger', '09120000072');

        $this->actingAs($stranger)
            ->post(route('ad.pause', $this->ad))
            ->assertForbidden();

        $this->assertNull($this->ad->fresh()->paused_at);
    }

    public function test_a_guest_cannot_pause_anything(): void
    {
        $this->post(route('ad.pause', $this->ad))->assertRedirect(route('login'));

        $this->assertNull($this->ad->fresh()->paused_at);
    }

    /*
    |--------------------------------------------------------------------------
    | پنل
    |--------------------------------------------------------------------------
    */
    public function test_the_panel_offers_the_right_button_for_each_state(): void
    {
        $this->actingAs($this->owner)
            ->get(route('product.panel'))
            ->assertOk()
            ->assertSee('خاموش کردن موقت', false)
            ->assertDontSee('روشن کردن آگهی', false);

        $this->pause();

        $this->actingAs($this->owner)
            ->get(route('product.panel'))
            ->assertOk()
            ->assertSee('روشن کردن آگهی', false)
            ->assertSee('موقتاً خاموش', false);
    }

    /*
    | تعلیقِ سیستمی مقدم است: کاربر باید اول آن را حل کند، چون
    | روشن‌کردن آگهی بدون اشتراک کاری از پیش نمی‌برد.
    */
    public function test_a_system_suspension_is_shown_ahead_of_a_self_pause(): void
    {
        $this->ad->forceFill([
            'is_suspended' => true,
            'suspended_at' => now(),
            'paused_at' => now(),
        ])->save();

        $this->assertSame('در حالت تعلیق', $this->ad->fresh()->status_text);
    }

    public function test_a_paused_ad_reads_as_paused(): void
    {
        $this->pause();

        $this->assertSame('موقتاً غیرفعال', $this->ad->fresh()->status_text);
    }

    /*
    |--------------------------------------------------------------------------
    | کمکی
    |--------------------------------------------------------------------------
    */
    private function pause(): void
    {
        $this->actingAs($this->owner)
            ->post(route('ad.pause', $this->ad))
            ->assertRedirect();
    }

    private function resume(): void
    {
        $this->actingAs($this->owner)
            ->post(route('ad.resume', $this->ad))
            ->assertRedirect();
    }

    private function user(string $handle, string $mobile): User
    {
        return User::create([
            'name' => $handle, 'username' => $handle,
            'mobile' => $mobile, 'password' => 'secret-password',
        ]);
    }

    private function makeAd(): Ad
    {
        $province = Province::firstOrCreate(['slug' => 'tehran'], ['name' => 'تهران']);
        $city = City::firstOrCreate(
            ['slug' => 'tehran', 'province_id' => $province->id],
            ['name' => 'تهران']
        );
        $category = Category::firstOrCreate(
            ['slug' => 'masaleh'],
            ['name' => 'مصالح', 'type' => 'product', 'is_active' => true]
        );

        return Ad::create([
            'user_id' => $this->owner->id,
            'category_id' => $category->id,
            'province_id' => $province->id,
            'city_id' => $city->id,
            'type' => 'product',
            'title' => 'سیمان تیپ دو',
            'price' => 1000,
            'address' => 'آدرس',
            'phone' => '09121234567',
            'status' => 'approved',
            'is_featured' => true,
        ]);
    }
}
