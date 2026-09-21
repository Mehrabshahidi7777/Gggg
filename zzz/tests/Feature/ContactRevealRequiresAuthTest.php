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
| دیدن شماره تماس نیاز به حساب دارد
|--------------------------------------------------------------------------
|
| تا پیش از این مهمان هم می‌توانست شماره ببیند. دو چیز را خراب می‌کرد:
|
| ۱. «شماره‌هایی که دیده‌ام» در پنل کاربر ساختنی نبود، چون نمایشِ
|    بی‌نام به هیچ حسابی وصل نیست.
|
| ۲. شمارش یکتا فقط روی هشِ IP بود، که برای دو نفر پشت یک مودم یکی
|    است.
|
| حالا مهمان به ثبت‌نام فرستاده می‌شود و مقصد در سشن می‌ماند تا بعد
| از ساخت حساب به همان آگهی برگردد.
|
*/
class ContactRevealRequiresAuthTest extends TestCase
{
    use RefreshDatabase;

    private Ad $ad;
    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = $this->makeUser('seller', '09120000001');

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
    | دروازه
    |--------------------------------------------------------------------------
    */
    public function test_a_guest_is_sent_to_register_instead_of_getting_the_number(): void
    {
        $response = $this->postJson(route('ad.contact', $this->ad))->assertStatus(401);

        $response->assertJson([
            'success' => false,
            'requires_auth' => true,
            'url' => route('register'),
        ]);

        // مهم‌ترین بخش: شماره نباید در پاسخ باشد.
        $this->assertArrayNotHasKey('phone', $response->json());
    }

    public function test_the_guest_is_brought_back_to_the_same_ad_after_signing_up(): void
    {
        $this->postJson(route('ad.contact', $this->ad))->assertStatus(401);

        $this->assertSame(
            route('ad.show', $this->ad->slug),
            session('url.intended')
        );
    }

    /*
    | ثبت‌نام با ایمیل تنها مسیری بود که intended را نادیده می‌گرفت و
    | کاربر را به خانه می‌انداخت - یعنی آگهی گم می‌شد.
    */
    public function test_registering_by_email_returns_to_the_ad(): void
    {
        $this->postJson(route('ad.contact', $this->ad))->assertStatus(401);

        $this->post(route('register.store'), [
            'username' => 'tazehvared',
            'email' => 'tazeh@example.com',
            'password' => 'secret-password',
            'password_confirmation' => 'secret-password',
        ])->assertRedirect(route('ad.show', $this->ad->slug));
    }

    public function test_a_signed_in_user_gets_the_number(): void
    {
        $this->actingAs($this->makeUser('buyer', '09120000002'))
            ->postJson(route('ad.contact', $this->ad))
            ->assertOk()
            ->assertJson(['success' => true, 'phone' => '09121234567']);
    }

    /*
    |--------------------------------------------------------------------------
    | ثبت نمایش
    |--------------------------------------------------------------------------
    */
    public function test_the_reveal_is_recorded_against_the_account(): void
    {
        $buyer = $this->makeUser('buyer2', '09120000003');

        $this->actingAs($buyer)->postJson(route('ad.contact', $this->ad))->assertOk();

        $this->assertDatabaseHas('ad_contact_reveals', [
            'ad_id' => $this->ad->id,
            'user_id' => $buyer->id,
        ]);
    }

    /*
    | همان اشتباهی که کلید یکتای قدیمی مرتکب می‌شد: دو نفر پشت یک IP.
    | هش IP در تست یکی است چون هر دو درخواست از 127.0.0.1 می‌آیند.
    */
    public function test_two_people_behind_one_ip_are_both_recorded(): void
    {
        $this->actingAs($this->makeUser('a', '09120000004'))
            ->postJson(route('ad.contact', $this->ad))->assertOk();

        $this->actingAs($this->makeUser('b', '09120000005'))
            ->postJson(route('ad.contact', $this->ad))->assertOk();

        $this->assertSame(2, AdContactReveal::count());
    }

    /*
    | ولی یک نفر که دو بار در یک روز می‌زند، یک بار شمرده می‌شود -
    | وگرنه هم آمار ارائه‌دهنده باد می‌کند و هم پنل خودش تکراری
    | نشان می‌دهد.
    */
    public function test_the_same_person_twice_in_one_day_is_recorded_once(): void
    {
        $buyer = $this->makeUser('c', '09120000006');

        $this->actingAs($buyer)->postJson(route('ad.contact', $this->ad))->assertOk();
        $this->actingAs($buyer)->postJson(route('ad.contact', $this->ad))->assertOk();

        $this->assertSame(1, AdContactReveal::count());
    }

    /*
    | صاحب آگهی شمرده نمی‌شود، وگرنه هر بار که آگهی خودش را باز کند
    | آمار سرنخ‌هایش بی‌معنی می‌شود.
    */
    public function test_the_owner_still_sees_the_number_without_being_counted(): void
    {
        $this->actingAs($this->owner)
            ->postJson(route('ad.contact', $this->ad))
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertSame(0, AdContactReveal::count());
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
