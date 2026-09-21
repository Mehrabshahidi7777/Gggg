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

class ContactRevealTest extends TestCase
{
    use RefreshDatabase;

    private Ad $ad;
    private User $owner;

    /*
    | این تست‌ها قبلاً مهمان بودند، چون دیدن شماره باز بود. حالا
    | نیاز به حساب دارد و قاعده‌ی تازه جای خودش تست می‌شود:
    | ContactRevealRequiresAuthTest. اینجا بازدیدکننده وارد شده تا
    | چیزی که واقعاً موضوع این فایل است - ضد اسکرپ، شمارش سرنخ و
    | نگه‌نداشتن IP خام - همچنان تست شود.
    */
    private User $visitor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::create([
            'name' => 'فروشنده', 'username' => 'seller',
            'mobile' => '09120000001', 'password' => 'secret-password',
        ]);

        $province = Province::create(['name' => 'تهران', 'slug' => 'tehran']);
        $city = City::create(['name' => 'تهران', 'slug' => 'tehran', 'province_id' => $province->id]);
        $category = Category::create(['name' => 'د', 'slug' => 'd', 'type' => 'product', 'is_active' => true]);

        $this->visitor = User::create([
            'name' => 'بازدیدکننده', 'username' => 'visitor',
            'mobile' => '09120000002', 'password' => 'secret-password',
        ]);

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
    | نکته‌ی اصلی ضد اسکرپ: شماره نباید در HTML صفحه باشد.
    */
    public function test_phone_is_not_present_in_the_page_html(): void
    {
        $this->get(route('ad.show', $this->ad->slug))
            ->assertOk()
            ->assertSee('نمایش شماره', false)
            ->assertDontSee('09121234567');
    }

    public function test_phone_is_returned_by_the_contact_endpoint(): void
    {
        $this->actingAs($this->visitor)
            ->postJson(route('ad.contact', $this->ad))
            ->assertOk()
            ->assertJson(['success' => true, 'phone' => '09121234567']);
    }

    public function test_reveal_is_recorded_as_a_lead(): void
    {
        $this->actingAs($this->visitor)->postJson(route('ad.contact', $this->ad))->assertOk();

        $this->assertDatabaseCount('ad_contact_reveals', 1);
        $this->assertDatabaseHas('ad_contact_reveals', ['ad_id' => $this->ad->id]);
    }

    /*
    | یک بازدیدکننده در یک روز فقط یک بار شمرده می‌شود، وگرنه رفرش
    | کردن صفحه آمار را بی‌معنی می‌کند.
    */
    public function test_same_visitor_counted_once_per_day(): void
    {
        $this->actingAs($this->visitor);

        $this->postJson(route('ad.contact', $this->ad))->assertOk();
        $this->postJson(route('ad.contact', $this->ad))->assertOk();
        $this->postJson(route('ad.contact', $this->ad))->assertOk();

        $this->assertDatabaseCount('ad_contact_reveals', 1);
    }

    /*
    | صاحب آگهی نباید آمار خودش را بالا ببرد.
    */
    public function test_owner_viewing_own_ad_is_not_counted(): void
    {
        $this->actingAs($this->owner)
            ->postJson(route('ad.contact', $this->ad))
            ->assertOk();

        $this->assertDatabaseCount('ad_contact_reveals', 0);
    }

    public function test_suspended_ad_does_not_expose_phone(): void
    {
        $this->ad->update(['is_suspended' => true, 'suspended_at' => now()]);

        $this->actingAs($this->visitor)
            ->postJson(route('ad.contact', $this->ad))
            ->assertNotFound();
    }

    public function test_raw_ip_is_never_stored(): void
    {
        $this->actingAs($this->visitor)->postJson(route('ad.contact', $this->ad))->assertOk();

        $reveal = AdContactReveal::first();

        $this->assertNotNull($reveal->ip_hash);
        $this->assertSame(64, strlen($reveal->ip_hash));
        $this->assertStringNotContainsString('127.0.0.1', $reveal->ip_hash);
    }

    public function test_panel_shows_lead_counts(): void
    {
        AdContactReveal::create([
            'ad_id' => $this->ad->id,
            'ip_hash' => str_repeat('a', 64),
            'revealed_on' => now()->toDateString(),
            'created_at' => now(),
        ]);

        $this->actingAs($this->owner)
            ->get(route('product.panel'))
            ->assertOk()
            ->assertSee('تماس‌های این ماه', false);
    }
}
