<?php

namespace Tests\Feature;

use App\Models\Ad;
use App\Models\AdContactReveal;
use App\Models\AdRating;
use App\Models\Category;
use App\Models\City;
use App\Models\Province;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| پنل کاربر: فعالیت من
|--------------------------------------------------------------------------
|
| «پنل مشتری» سفارش‌ها را نشان می‌داد، ولی خرید آنلاین خاموش است و
| تماس مستقیم انجام می‌شود - پس آن صفحه همیشه سه صفر نشان می‌داد و
| عملاً به کاربر می‌گفت «اینجا هیچ خبری نیست».
|
| حالا همان سه چیزی که کاربرِ این سایت واقعاً انجام می‌دهد آنجاست:
| ستاره، نظر، و شماره‌هایی که دیده.
|
*/
class ActivityPanelTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Ad $ad;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = $this->makeUser('bazdidkonande', '09120000010');
        $owner = $this->makeUser('forushande', '09120000011');

        $province = Province::create(['name' => 'تهران', 'slug' => 'tehran']);
        $city = City::create(['name' => 'تهران', 'slug' => 'tehran', 'province_id' => $province->id]);
        $category = Category::create(['name' => 'مصالح', 'slug' => 'masaleh', 'type' => 'product', 'is_active' => true]);

        $this->ad = Ad::create([
            'user_id' => $owner->id,
            'category_id' => $category->id,
            'province_id' => $province->id,
            'city_id' => $city->id,
            'type' => 'product',
            'title' => 'سیمان تیپ دو',
            'price' => 1000,
            'address' => 'آدرس',
            'phone' => '09121234567',
            'status' => 'approved',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | دسترسی
    |--------------------------------------------------------------------------
    */
    public function test_a_guest_cannot_open_the_panel(): void
    {
        $this->get(route('activity'))->assertRedirect(route('login'));
    }

    public function test_the_menu_points_at_the_activity_panel_not_the_orders_page(): void
    {
        $html = $this->actingAs($this->user)->get(route('home'))->assertOk()->getContent();

        $this->assertStringContainsString('فعالیت من', $html);
        $this->assertStringContainsString(route('activity'), $html);

        /*
        | «پنل مشتری» همان صفحه‌ی همیشه‌خالی بود. اگر دوباره در منو
        | ظاهر شود، یعنی تغییر برگشته.
        */
        $this->assertStringNotContainsString('پنل مشتری', $html);
    }

    /*
    |--------------------------------------------------------------------------
    | شماره‌هایی که دیده‌ام
    |--------------------------------------------------------------------------
    */
    public function test_a_revealed_number_shows_up_with_its_ad(): void
    {
        $this->actingAs($this->user)->postJson(route('ad.contact', $this->ad))->assertOk();

        $this->actingAs($this->user)
            ->get(route('activity'))
            ->assertOk()
            ->assertSee('سیمان تیپ دو')
            ->assertSee('09121234567');
    }

    /*
    | مهم‌ترین مرز این صفحه: فعالیت یک نفر نباید در پنل نفر دیگر
    | دیده شود.
    */
    public function test_another_persons_reveals_are_not_listed(): void
    {
        $someoneElse = $this->makeUser('digari', '09120000012');

        $this->actingAs($someoneElse)->postJson(route('ad.contact', $this->ad))->assertOk();

        $this->actingAs($this->user)
            ->get(route('activity'))
            ->assertOk()
            ->assertDontSee('09121234567');
    }

    public function test_an_empty_panel_explains_what_will_appear_there(): void
    {
        $this->actingAs($this->user)
            ->get(route('activity'))
            ->assertOk()
            ->assertSee('هنوز شماره‌ای ندیده‌ای', false)
            ->assertSee('هنوز به آگهی‌ای امتیاز نداده‌ای', false);
    }

    /*
    |--------------------------------------------------------------------------
    | امتیازها و نظرها
    |--------------------------------------------------------------------------
    */
    public function test_a_rating_is_listed_with_its_label(): void
    {
        $this->actingAs($this->user)
            ->postJson(route('ad.rate', $this->ad), ['rating' => 4])
            ->assertOk();

        $this->actingAs($this->user)
            ->get(route('activity'))
            ->assertOk()
            ->assertSee('سیمان تیپ دو')
            ->assertSee(AdRating::LABELS[4], false);
    }

    /*
    | نظر در انتظار تأیید در صفحه‌ی آگهی دیده نمی‌شود، پس بدون این
    | صفحه کاربر فکر می‌کند نظرش گم شده است.
    */
    public function test_a_pending_comment_is_marked_as_waiting(): void
    {
        $this->actingAs($this->user)->postJson(route('ad.rate', $this->ad), [
            'rating' => 5,
            'comment' => 'جنسش خوب بود',
        ])->assertOk();

        $this->actingAs($this->user)
            ->get(route('activity'))
            ->assertOk()
            ->assertSee('جنسش خوب بود')
            ->assertSee('در انتظار تأیید', false);
    }

    public function test_a_rejected_comment_shows_its_reason(): void
    {
        AdRating::create([
            'ad_id' => $this->ad->id,
            'user_id' => $this->user->id,
            'rating' => 2,
            'comment' => 'متن رد شده',
            'comment_status' => 'rejected',
            'comment_rejection_reason' => 'شامل شماره تماس بود',
        ]);

        $this->actingAs($this->user)
            ->get(route('activity'))
            ->assertOk()
            ->assertSee('شامل شماره تماس بود');
    }

    /*
    |--------------------------------------------------------------------------
    | شمارنده‌ها
    |--------------------------------------------------------------------------
    */
    public function test_the_counters_separate_ratings_from_comments(): void
    {
        // یکی با نظر، یکی بدون نظر
        AdRating::create([
            'ad_id' => $this->ad->id,
            'user_id' => $this->user->id,
            'rating' => 5,
            'comment' => 'عالی',
            'comment_status' => 'approved',
        ]);

        $otherAd = Ad::create([
            'user_id' => $this->ad->user_id,
            'category_id' => $this->ad->category_id,
            'province_id' => $this->ad->province_id,
            'city_id' => $this->ad->city_id,
            'type' => 'product',
            'title' => 'آجر',
            'price' => 500,
            'address' => 'آدرس',
            'phone' => '09129999999',
            'status' => 'approved',
        ]);

        AdRating::create([
            'ad_id' => $otherAd->id,
            'user_id' => $this->user->id,
            'rating' => 3,
        ]);

        $response = $this->actingAs($this->user)->get(route('activity'))->assertOk();

        $response->assertViewHas('stats', [
            'ratings' => 2,
            'comments' => 1,
            'reveals' => 0,
        ]);
    }

    /*
    | آگهی حذف‌شده نباید صفحه را بشکند - ردیف می‌ماند و می‌گوید
    | آگهی رفته است.
    */
    public function test_a_deleted_ad_leaves_a_readable_row(): void
    {
        AdContactReveal::create([
            'ad_id' => $this->ad->id,
            'user_id' => $this->user->id,
            'ip_hash' => str_repeat('a', 64),
            'revealed_on' => now()->toDateString(),
        ]);

        $this->ad->delete();

        $this->actingAs($this->user)
            ->get(route('activity'))
            ->assertOk();
    }

    /*
    |--------------------------------------------------------------------------
    | سفارش‌های قدیمی
    |--------------------------------------------------------------------------
    |
    | لینکشان فقط برای کسی که واقعاً سفارشی دارد - وگرنه دوباره همان
    | صفحه‌ی خالی است.
    */
    public function test_someone_with_no_orders_is_not_offered_the_orders_page(): void
    {
        $this->actingAs($this->user)
            ->get(route('activity'))
            ->assertOk()
            ->assertDontSee('سفارش‌های قبلی من', false);
    }

    private function makeUser(string $handle, string $mobile): User
    {
        return User::create([
            'name' => $handle,
            'username' => $handle,
            'mobile' => $mobile,
            'password' => 'secret-password',
        ]);
    }
}
