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

class AdRatingTest extends TestCase
{
    use RefreshDatabase;

    private function makeAd(User $owner, string $type = 'product'): Ad
    {
        $province = Province::create(['name' => 'تهران', 'slug' => 'tehran']);
        $city = City::create(['name' => 'تهران', 'slug' => 'tehran', 'province_id' => $province->id]);
        $category = Category::create([
            'name' => 'مصالح',
            'slug' => 'masaleh-' . $type,
            'type' => $type,
            'is_active' => true,
        ]);

        return Ad::create([
            'user_id' => $owner->id,
            'category_id' => $category->id,
            'province_id' => $province->id,
            'city_id' => $city->id,
            'type' => $type,
            'title' => 'آگهی تست',
            'description' => 'توضیح',
            'price' => 1000,
            'address' => 'خیابان تست، پلاک ۱',
            'phone' => '09121234567',
            'status' => 'approved',
        ]);
    }

    private function user(string $mobile): User
    {
        return User::create([
            'name' => 'کاربر',
            'username' => 'u' . $mobile,
            'mobile' => $mobile,
            'password' => 'secret-password',
        ]);
    }

    public function test_logged_in_user_can_rate_an_ad(): void
    {
        $owner = $this->user('09120000001');
        $rater = $this->user('09120000002');
        $ad = $this->makeAd($owner);

        $this->actingAs($rater)
            ->post(route('ad.rate', $ad), ['rating' => 4])
            ->assertRedirect();

        $this->assertDatabaseHas('ad_ratings', [
            'ad_id' => $ad->id,
            'user_id' => $rater->id,
            'rating' => 4,
        ]);
    }

    public function test_rating_again_updates_instead_of_duplicating(): void
    {
        $owner = $this->user('09120000001');
        $rater = $this->user('09120000002');
        $ad = $this->makeAd($owner);

        $this->actingAs($rater)->post(route('ad.rate', $ad), ['rating' => 1]);
        $this->actingAs($rater)->post(route('ad.rate', $ad), ['rating' => 5]);

        $this->assertSame(1, AdRating::where('ad_id', $ad->id)->count());
        $this->assertSame(5, AdRating::where('ad_id', $ad->id)->first()->rating);
    }

    public function test_owner_cannot_rate_own_ad(): void
    {
        $owner = $this->user('09120000001');
        $ad = $this->makeAd($owner);

        $this->actingAs($owner)->post(route('ad.rate', $ad), ['rating' => 5]);

        $this->assertDatabaseCount('ad_ratings', 0);
    }

    public function test_guest_cannot_rate(): void
    {
        $owner = $this->user('09120000001');
        $ad = $this->makeAd($owner);

        $this->post(route('ad.rate', $ad), ['rating' => 5])
            ->assertRedirect(route('login'));

        $this->assertDatabaseCount('ad_ratings', 0);
    }

    public function test_rating_must_be_between_one_and_five(): void
    {
        $owner = $this->user('09120000001');
        $rater = $this->user('09120000002');
        $ad = $this->makeAd($owner);

        $this->actingAs($rater)
            ->post(route('ad.rate', $ad), ['rating' => 9])
            ->assertSessionHasErrors('rating');

        $this->assertDatabaseCount('ad_ratings', 0);
    }

    public function test_suspended_ad_cannot_be_rated(): void
    {
        $owner = $this->user('09120000001');
        $rater = $this->user('09120000002');
        $ad = $this->makeAd($owner);
        $ad->update(['is_suspended' => true, 'suspended_at' => now()]);

        $this->actingAs($rater)
            ->post(route('ad.rate', $ad), ['rating' => 5])
            ->assertNotFound();
    }

    public function test_average_and_count_are_exposed_on_the_model(): void
    {
        $owner = $this->user('09120000001');
        $ad = $this->makeAd($owner);

        AdRating::create(['ad_id' => $ad->id, 'user_id' => $this->user('09120000002')->id, 'rating' => 4]);
        AdRating::create(['ad_id' => $ad->id, 'user_id' => $this->user('09120000003')->id, 'rating' => 5]);

        $loaded = Ad::withRatingSummary()->find($ad->id);

        $this->assertSame(4.5, $loaded->rating_average);
        $this->assertSame(2, $loaded->rating_count);
    }

    public function test_ad_page_shows_contact_details_for_both_types(): void
    {
        $owner = $this->user('09120000001');

        foreach (['product', 'service'] as $index => $type) {

            $ad = $this->makeAd($this->user('0912000100' . $index), $type);

            $response = $this->get(route('ad.show', $ad->slug));

            $response->assertOk();
            $response->assertSee('09121234567');
            $response->assertSee('خیابان تست، پلاک ۱', false);
            $response->assertSee('به این آگهی امتیاز بدهید', false);
        }
    }

    public function test_product_page_no_longer_offers_add_to_cart(): void
    {
        $owner = $this->user('09120000001');
        $ad = $this->makeAd($owner, 'product');

        $this->get(route('ad.show', $ad->slug))
            ->assertOk()
            ->assertDontSee('افزودن به سبد خرید', false);
    }

    public function test_cart_routes_are_disabled_while_checkout_is_off(): void
    {
        config(['marketplace.online_checkout' => false]);

        $this->get('/cart')->assertRedirect(route('products'));
    }
}
