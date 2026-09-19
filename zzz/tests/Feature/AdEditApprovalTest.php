<?php

namespace Tests\Feature;

use App\Models\Ad;
use App\Models\AdEdit;
use App\Models\AdImage;
use App\Models\Category;
use App\Models\City;
use App\Models\Province;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdEditApprovalTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;
    private User $admin;
    private Ad $ad;
    private Category $category;
    private Province $province;
    private City $city;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::create([
            'name' => 'فروشنده', 'username' => 'seller',
            'mobile' => '09120000001', 'password' => 'secret-password',
        ]);

        $this->admin = User::create([
            'name' => 'مدیر', 'username' => 'admin',
            'mobile' => '09120000002', 'password' => 'secret-password',
        ]);

        /*
        | is_admin عمداً در $fillable مدل User نیست تا هیچ mass
        | assignment ای نتواند دسترسی مدیر بدهد. پس اینجا هم باید
        | صریح ست شود، نه از راه create().
        */
        $this->admin->forceFill(['is_admin' => true])->save();

        $this->province = Province::create(['name' => 'تهران', 'slug' => 'tehran']);
        $this->city = City::create(['name' => 'تهران', 'slug' => 'tehran', 'province_id' => $this->province->id]);
        $this->category = Category::create(['name' => 'مصالح', 'slug' => 'masaleh', 'type' => 'product', 'is_active' => true]);

        $this->ad = Ad::create([
            'user_id' => $this->owner->id,
            'category_id' => $this->category->id,
            'province_id' => $this->province->id,
            'city_id' => $this->city->id,
            'type' => 'product',
            'title' => 'عنوان اولیه',
            'description' => 'توضیح اولیه',
            'price' => 100000,
            'address' => 'آدرس اولیه',
            'phone' => '09121111111',
            'card_number' => str_repeat('1', 24),
            'status' => 'approved',
        ]);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'title' => 'عنوان اولیه',
            'description' => 'توضیح اولیه',
            'price' => 100000,
            'address' => 'آدرس اولیه',
            'phone' => '09121111111',
            'card_number' => str_repeat('1', 24),
            'category_id' => $this->category->id,
            'province_id' => $this->province->id,
            'city_id' => $this->city->id,
        ], $overrides);
    }

    /*
    |--------------------------------------------------------------------------
    | ثبت درخواست
    |--------------------------------------------------------------------------
    */
    public function test_owner_can_submit_an_edit_request(): void
    {
        $this->actingAs($this->owner)
            ->put(route('ad.edit.store', $this->ad), $this->payload([
                'title' => 'عنوان تازه',
                'price' => 250000,
            ]))
            ->assertRedirect(route('product.panel'));

        $this->assertDatabaseCount('ad_edits', 1);

        $edit = AdEdit::first();

        $this->assertSame('pending', $edit->status);
        $this->assertSame('عنوان تازه', $edit->payload['title']);
        $this->assertSame('عنوان اولیه', $edit->original['title']);
    }

    /*
    | مهم‌ترین قانون: تا تأیید نشود، آگهیِ روی سایت دست نمی‌خورد.
    */
    public function test_submitting_an_edit_does_not_change_the_live_ad(): void
    {
        $this->actingAs($this->owner)
            ->put(route('ad.edit.store', $this->ad), $this->payload(['title' => 'عنوان تازه']));

        $this->assertSame('عنوان اولیه', $this->ad->fresh()->title);
    }

    public function test_only_changed_fields_are_recorded(): void
    {
        $this->actingAs($this->owner)
            ->put(route('ad.edit.store', $this->ad), $this->payload(['price' => 999000]));

        $edit = AdEdit::first();

        $this->assertSame(['price'], array_keys($edit->payload));
    }

    public function test_submitting_with_no_changes_is_rejected(): void
    {
        $this->actingAs($this->owner)
            ->put(route('ad.edit.store', $this->ad), $this->payload())
            ->assertSessionHas('error');

        $this->assertDatabaseCount('ad_edits', 0);
    }

    public function test_a_second_pending_request_is_blocked(): void
    {
        $this->actingAs($this->owner)
            ->put(route('ad.edit.store', $this->ad), $this->payload(['title' => 'اول']));

        $this->actingAs($this->owner)
            ->put(route('ad.edit.store', $this->ad), $this->payload(['title' => 'دوم']))
            ->assertSessionHas('error');

        $this->assertDatabaseCount('ad_edits', 1);
    }

    public function test_another_user_cannot_edit_someone_elses_ad(): void
    {
        $stranger = User::create([
            'name' => 'غریبه', 'username' => 'stranger',
            'mobile' => '09120000003', 'password' => 'secret-password',
        ]);

        $this->actingAs($stranger)
            ->put(route('ad.edit.store', $this->ad), $this->payload(['title' => 'هک']))
            ->assertForbidden();

        $this->assertDatabaseCount('ad_edits', 0);
    }

    /*
    | ارائه‌دهنده نباید بتواند وضعیت انتشار یا نشان ویژه را دست‌کاری
    | کند، حتی اگر این فیلدها را دستی به درخواست اضافه کند.
    */
    public function test_provider_cannot_smuggle_privileged_fields(): void
    {
        $this->actingAs($this->owner)
            ->put(route('ad.edit.store', $this->ad), $this->payload([
                'title' => 'عنوان تازه',
                'is_featured' => 1,
                'status' => 'approved',
                'expires_at' => now()->addYears(10)->toDateString(),
            ]));

        $edit = AdEdit::first();

        $this->assertArrayNotHasKey('is_featured', $edit->payload);
        $this->assertArrayNotHasKey('status', $edit->payload);
        $this->assertArrayNotHasKey('expires_at', $edit->payload);
    }

    public function test_phone_with_persian_digits_is_normalised(): void
    {
        $this->actingAs($this->owner)
            ->put(route('ad.edit.store', $this->ad), $this->payload(['phone' => '۰۹۱۲۳۳۳۳۳۳۳']));

        $this->assertSame('09123333333', AdEdit::first()->payload['phone']);
    }

    /*
    |--------------------------------------------------------------------------
    | تأیید و رد
    |--------------------------------------------------------------------------
    */
    public function test_admin_approval_applies_the_changes(): void
    {
        $this->actingAs($this->owner)
            ->put(route('ad.edit.store', $this->ad), $this->payload([
                'title' => 'عنوان تازه',
                'price' => 250000,
            ]));

        $edit = AdEdit::first();

        $this->actingAs($this->admin)
            ->post(route('admin.ad-edits.approve', $edit))
            ->assertRedirect(route('admin.ad-edits.index'));

        $this->ad->refresh();

        $this->assertSame('عنوان تازه', $this->ad->title);
        $this->assertSame('250000.00', $this->ad->price);
        $this->assertSame('approved', $edit->fresh()->status);
        $this->assertSame($this->admin->id, $edit->fresh()->reviewed_by);
    }

    /*
    | آدرس صفحه نباید با تأیید ویرایشِ عنوان عوض شود، وگرنه هر لینکی
    | که به این آگهی داده شده ۴۰۴ می‌شود.
    */
    public function test_approving_a_title_change_keeps_the_slug(): void
    {
        $original = $this->ad->slug;

        $this->actingAs($this->owner)
            ->put(route('ad.edit.store', $this->ad), $this->payload(['title' => 'یک عنوان کاملاً متفاوت']));

        $this->actingAs($this->admin)->post(route('admin.ad-edits.approve', AdEdit::first()));

        $this->assertSame($original, $this->ad->fresh()->slug);
    }

    public function test_rejection_requires_a_reason_and_changes_nothing(): void
    {
        $this->actingAs($this->owner)
            ->put(route('ad.edit.store', $this->ad), $this->payload(['title' => 'عنوان تازه']));

        $edit = AdEdit::first();

        $this->actingAs($this->admin)
            ->post(route('admin.ad-edits.reject', $edit), [])
            ->assertSessionHasErrors('rejection_reason');

        $this->actingAs($this->admin)
            ->post(route('admin.ad-edits.reject', $edit), ['rejection_reason' => 'عنوان نامناسب است']);

        $this->assertSame('rejected', $edit->fresh()->status);
        $this->assertSame('عنوان اولیه', $this->ad->fresh()->title);
    }

    public function test_an_already_reviewed_request_cannot_be_reviewed_again(): void
    {
        $this->actingAs($this->owner)
            ->put(route('ad.edit.store', $this->ad), $this->payload(['title' => 'عنوان تازه']));

        $edit = AdEdit::first();

        $this->actingAs($this->admin)->post(route('admin.ad-edits.approve', $edit));

        $this->actingAs($this->admin)
            ->post(route('admin.ad-edits.approve', $edit))
            ->assertSessionHas('error');
    }

    public function test_non_admin_cannot_reach_the_review_queue(): void
    {
        $this->actingAs($this->owner)
            ->get(route('admin.ad-edits.index'))
            ->assertForbidden();
    }

    /*
    |--------------------------------------------------------------------------
    | تصاویر
    |--------------------------------------------------------------------------
    */
    public function test_new_images_appear_only_after_approval(): void
    {
        Storage::fake('public');

        $this->actingAs($this->owner)->put(route('ad.edit.store', $this->ad), $this->payload([
            'images' => [UploadedFile::fake()->image('new.jpg', 60, 60)],
        ]));

        // هنوز به آگهی وصل نشده
        $this->assertDatabaseCount('ad_images', 0);

        $this->actingAs($this->admin)->post(route('admin.ad-edits.approve', AdEdit::first()));

        $this->assertDatabaseCount('ad_images', 1);
    }

    public function test_rejected_upload_files_are_removed_from_disk(): void
    {
        Storage::fake('public');

        $this->actingAs($this->owner)->put(route('ad.edit.store', $this->ad), $this->payload([
            'images' => [UploadedFile::fake()->image('new.jpg', 60, 60)],
        ]));

        $path = AdEdit::first()->added_images[0];
        Storage::disk('public')->assertExists($path);

        $this->actingAs($this->admin)->post(
            route('admin.ad-edits.reject', AdEdit::first()),
            ['rejection_reason' => 'تصویر نامرتبط است']
        );

        Storage::disk('public')->assertMissing($path);
    }

    public function test_image_removal_is_applied_on_approval(): void
    {
        Storage::fake('public');

        $image = AdImage::create([
            'ad_id' => $this->ad->id,
            'path' => 'ads/old.jpg',
            'is_primary' => true,
        ]);
        Storage::disk('public')->put('ads/old.jpg', 'x');

        $this->actingAs($this->owner)->put(route('ad.edit.store', $this->ad), $this->payload([
            'delete_images' => [$image->id],
        ]));

        // هنوز حذف نشده
        $this->assertDatabaseCount('ad_images', 1);

        $this->actingAs($this->admin)->post(route('admin.ad-edits.approve', AdEdit::first()));

        $this->assertDatabaseCount('ad_images', 0);
        Storage::disk('public')->assertMissing('ads/old.jpg');
    }

    public function test_owner_can_cancel_a_pending_request(): void
    {
        $this->actingAs($this->owner)
            ->put(route('ad.edit.store', $this->ad), $this->payload(['title' => 'عنوان تازه']));

        $this->actingAs($this->owner)
            ->delete(route('ad.edit.cancel', $this->ad))
            ->assertSessionHas('success');

        $this->assertDatabaseCount('ad_edits', 0);
    }

    /*
    | ستون ads.address در دیتابیس varchar(255) است. اگر قانون
    | اعتبارسنجی بیشتر اجازه بدهد، MySQL در حالت strict خطای
    | «Data too long» می‌دهد و کاربر به‌جای پیام خطا صفحه‌ی ۵۰۰
    | می‌بیند. این تست جلوی برگشت آن اشتباه را می‌گیرد.
    */
    public function test_address_longer_than_the_column_is_rejected_cleanly(): void
    {
        $this->actingAs($this->owner)
            ->put(route('ad.edit.store', $this->ad), $this->payload([
                'address' => str_repeat('ا', 256),
            ]))
            ->assertSessionHasErrors('address');

        $this->assertDatabaseCount('ad_edits', 0);
    }

    public function test_address_at_the_column_limit_is_accepted(): void
    {
        $this->actingAs($this->owner)
            ->put(route('ad.edit.store', $this->ad), $this->payload([
                'address' => str_repeat('ا', 255),
            ]))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('ad_edits', 1);
    }

    public function test_admin_sees_a_readable_diff(): void
    {
        $this->actingAs($this->owner)
            ->put(route('ad.edit.store', $this->ad), $this->payload([
                'title' => 'عنوان تازه',
                'price' => 250000,
            ]));

        $this->actingAs($this->admin)
            ->get(route('admin.ad-edits.show', AdEdit::first()))
            ->assertOk()
            ->assertSee('عنوان', false)
            ->assertSee('عنوان اولیه', false)
            ->assertSee('عنوان تازه', false)
            ->assertSee('قیمت', false);
    }
}
