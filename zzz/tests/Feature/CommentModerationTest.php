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

class CommentModerationTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;
    private User $visitor;
    private User $admin;
    private Ad $ad;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::create([
            'name' => 'فروشنده', 'username' => 'seller',
            'mobile' => '09120000001', 'password' => 'secret-password',
        ]);

        $this->visitor = User::create([
            'name' => 'بازدیدکننده', 'username' => 'visitor',
            'mobile' => '09120000002', 'password' => 'secret-password',
        ]);

        $this->admin = User::create([
            'name' => 'مدیر', 'username' => 'admin',
            'mobile' => '09120000003', 'password' => 'secret-password',
        ]);
        $this->admin->forceFill(['is_admin' => true])->save();

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
    | ثبت نظر
    |--------------------------------------------------------------------------
    */
    public function test_comment_is_stored_as_pending(): void
    {
        $this->actingAs($this->visitor)
            ->postJson(route('ad.rate', $this->ad), [
                'rating' => 5,
                'comment' => 'کیفیت عالی بود، پیشنهاد می‌کنم.',
            ])
            ->assertOk();

        $rating = AdRating::first();

        $this->assertSame('pending', $rating->comment_status);
        $this->assertSame(5, $rating->rating);
    }

    /*
    | ستاره فوری اعمال می‌شود حتی وقتی متن در صف تأیید است.
    */
    public function test_star_counts_immediately_even_while_comment_waits(): void
    {
        $this->actingAs($this->visitor)->postJson(route('ad.rate', $this->ad), [
            'rating' => 4,
            'comment' => 'متن در انتظار تأیید',
        ]);

        $loaded = Ad::withRatingSummary()->find($this->ad->id);

        $this->assertSame(4.0, $loaded->rating_average);
        $this->assertSame(1, $loaded->rating_count);
    }

    public function test_user_is_told_the_comment_awaits_approval(): void
    {
        $response = $this->actingAs($this->visitor)->postJson(route('ad.rate', $this->ad), [
            'rating' => 5,
            'comment' => 'خیلی خوب بود',
        ])->assertOk();

        $this->assertStringContainsString('پس از تأیید', $response->json('message'));
    }

    public function test_rating_without_a_comment_creates_no_moderation_item(): void
    {
        $this->actingAs($this->visitor)
            ->postJson(route('ad.rate', $this->ad), ['rating' => 3])
            ->assertOk();

        $this->assertNull(AdRating::first()->comment_status);
    }

    /*
    |--------------------------------------------------------------------------
    | نمایش عمومی
    |--------------------------------------------------------------------------
    */
    public function test_pending_comment_is_not_shown_to_other_visitors(): void
    {
        AdRating::create([
            'ad_id' => $this->ad->id,
            'user_id' => $this->visitor->id,
            'rating' => 5,
            'comment' => 'متن-محرمانه-در-انتظار',
            'comment_status' => 'pending',
        ]);

        // مهمان
        $this->get(route('ad.show', $this->ad->slug))
            ->assertOk()
            ->assertDontSee('متن-محرمانه-در-انتظار', false);

        // کاربر دیگری غیر از نویسنده
        $this->actingAs($this->owner)
            ->get(route('ad.show', $this->ad->slug))
            ->assertOk()
            ->assertDontSee('متن-محرمانه-در-انتظار', false);
    }

    /*
    | اما خودِ نویسنده باید متنش را در کادر ویرایش ببیند، وگرنه
    | نمی‌فهمد چه فرستاده و نمی‌تواند اصلاحش کند.
    */
    public function test_author_sees_their_own_pending_comment_in_the_edit_box(): void
    {
        AdRating::create([
            'ad_id' => $this->ad->id,
            'user_id' => $this->visitor->id,
            'rating' => 5,
            'comment' => 'متن-خودم',
            'comment_status' => 'pending',
        ]);

        $this->actingAs($this->visitor)
            ->get(route('ad.show', $this->ad->slug))
            ->assertOk()
            ->assertSee('متن-خودم', false)
            ->assertSee('پس از تأیید مدیر نمایش داده می‌شود', false);
    }

    public function test_approved_comment_is_shown_on_the_ad_page(): void
    {
        $this->actingAs($this->visitor)->postJson(route('ad.rate', $this->ad), [
            'rating' => 5,
            'comment' => 'متن-تاییدشده-برای-نمایش',
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.comments.approve', AdRating::first()))
            ->assertSessionHas('success');

        $this->get(route('ad.show', $this->ad->slug))
            ->assertOk()
            ->assertSee('متن-تاییدشده-برای-نمایش', false);
    }

    public function test_rejected_comment_is_not_shown_but_the_star_survives(): void
    {
        $this->actingAs($this->visitor)->postJson(route('ad.rate', $this->ad), [
            'rating' => 2,
            'comment' => 'متن-ردشده',
        ]);

        $this->actingAs($this->admin)->post(
            route('admin.comments.reject', AdRating::first()),
            ['comment_rejection_reason' => 'لحن نامناسب']
        );

        $this->get(route('ad.show', $this->ad->slug))
            ->assertOk()
            ->assertDontSee('متن-ردشده', false);

        // امتیاز باید دست‌نخورده بماند
        $this->assertSame(2, AdRating::first()->rating);
        $this->assertSame(2.0, Ad::withRatingSummary()->find($this->ad->id)->rating_average);
    }

    /*
    | حفره‌ی کلاسیک: متن بی‌ضرر بنویس، تأیید بگیر، بعد عوضش کن.
    */
    public function test_editing_an_approved_comment_sends_it_back_for_review(): void
    {
        $this->actingAs($this->visitor)->postJson(route('ad.rate', $this->ad), [
            'rating' => 5, 'comment' => 'متن بی‌ضرر',
        ]);

        $this->actingAs($this->admin)->post(route('admin.comments.approve', AdRating::first()));
        $this->assertSame('approved', AdRating::first()->comment_status);

        $this->actingAs($this->visitor)->postJson(route('ad.rate', $this->ad), [
            'rating' => 5, 'comment' => 'متن عوض‌شده بعد از تأیید',
        ]);

        $this->assertSame('pending', AdRating::first()->comment_status);
    }

    public function test_resubmitting_the_same_approved_text_stays_approved(): void
    {
        $this->actingAs($this->visitor)->postJson(route('ad.rate', $this->ad), [
            'rating' => 5, 'comment' => 'همان متن',
        ]);

        $this->actingAs($this->admin)->post(route('admin.comments.approve', AdRating::first()));

        // فقط ستاره را عوض می‌کند، متن همان است
        $this->actingAs($this->visitor)->postJson(route('ad.rate', $this->ad), [
            'rating' => 4, 'comment' => 'همان متن',
        ]);

        $this->assertSame('approved', AdRating::first()->comment_status);
        $this->assertSame(4, AdRating::first()->rating);
    }

    public function test_clearing_the_text_removes_the_comment_but_keeps_the_rating(): void
    {
        $this->actingAs($this->visitor)->postJson(route('ad.rate', $this->ad), [
            'rating' => 5, 'comment' => 'یک نظر',
        ]);

        $this->actingAs($this->visitor)->postJson(route('ad.rate', $this->ad), [
            'rating' => 5, 'comment' => '',
        ]);

        $rating = AdRating::first();

        $this->assertNull($rating->comment);
        $this->assertNull($rating->comment_status);
        $this->assertSame(5, $rating->rating);
    }

    public function test_owner_cannot_comment_on_own_ad(): void
    {
        $this->actingAs($this->owner)->postJson(route('ad.rate', $this->ad), [
            'rating' => 5, 'comment' => 'آگهی خودم عالی است',
        ]);

        $this->assertDatabaseCount('ad_ratings', 0);
    }

    public function test_comment_length_is_capped(): void
    {
        $this->actingAs($this->visitor)
            ->postJson(route('ad.rate', $this->ad), [
                'rating' => 5,
                'comment' => str_repeat('ا', 1001),
            ])
            ->assertStatus(422);
    }

    /*
    |--------------------------------------------------------------------------
    | پنل ادمین
    |--------------------------------------------------------------------------
    */
    public function test_moderation_queue_lists_only_comments_with_text(): void
    {
        // این یکی فقط ستاره دارد و نباید در صف باشد
        AdRating::create([
            'ad_id' => $this->ad->id,
            'user_id' => $this->visitor->id,
            'rating' => 3,
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.comments.index'))
            ->assertOk()
            ->assertSee('نظری در این وضعیت وجود ندارد', false);
    }

    public function test_non_admin_cannot_moderate(): void
    {
        $this->actingAs($this->visitor)->postJson(route('ad.rate', $this->ad), [
            'rating' => 5, 'comment' => 'نظر',
        ]);

        $this->actingAs($this->visitor)
            ->post(route('admin.comments.approve', AdRating::first()))
            ->assertForbidden();

        $this->assertSame('pending', AdRating::first()->comment_status);
    }

    public function test_a_reviewed_comment_cannot_be_reviewed_again(): void
    {
        $this->actingAs($this->visitor)->postJson(route('ad.rate', $this->ad), [
            'rating' => 5, 'comment' => 'نظر',
        ]);

        $this->actingAs($this->admin)->post(route('admin.comments.approve', AdRating::first()));

        $this->actingAs($this->admin)
            ->post(route('admin.comments.approve', AdRating::first()))
            ->assertSessionHas('error');
    }
}
