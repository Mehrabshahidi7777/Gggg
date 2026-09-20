<?php

namespace Tests\Feature;

use App\Models\Ad;
use App\Models\AdImage;
use App\Models\Category;
use App\Models\City;
use App\Models\Province;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| سقف تصاویر یک آگهی
|--------------------------------------------------------------------------
|
| قانون images|max فقط می‌گوید «در این درخواست بیشتر از ۱۰ فایل نفرست».
| ولی ویرایش، تصویر را به تصاویرِ موجود اضافه می‌کند، پس به‌تنهایی جلوی
| هیچ‌چیز را نمی‌گیرد: ۱۰ تا موقع ثبت، ۱۰ تا در ویرایش اول، ۱۰ تای دیگر
| در ویرایش دوم - و همین‌طور بی‌انتها، هر فایل تا ۱۰ مگابایت.
|
| این تست‌ها سقفِ خودِ آگهی را قفل می‌کنند.
|
*/
class AdImageLimitTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;
    private Ad $ad;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->owner = User::create([
            'name' => 'فروشنده', 'username' => 'seller',
            'mobile' => '09120000001', 'password' => 'secret-password',
        ]);

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
            'card_number' => str_repeat('3', 24),
            'status' => 'approved',
        ]);
    }

    public function test_the_cap_is_ten(): void
    {
        $this->assertSame(10, Ad::MAX_IMAGES);
    }

    /*
    | همان حفره‌ای که این تست‌ها برایش نوشته شده‌اند: آگهی از قبل پر
    | است و ویرایش می‌خواهد باز هم اضافه کند.
    */
    public function test_a_full_ad_cannot_gain_more_images_through_an_edit(): void
    {
        $this->giveTheAdImages(Ad::MAX_IMAGES);

        $this->submitEdit(['images' => [UploadedFile::fake()->image('extra.jpg')]])
            ->assertSessionHasErrors('images');

        $this->assertDatabaseCount('ad_edits', 0);
    }

    /*
    | و مهم‌تر: هیچ فایلی نباید روی دیسک نوشته شده باشد. اگر بررسی بعد
    | از آپلود انجام می‌شد، هر تلاشِ ناموفق هم فضا مصرف می‌کرد.
    */
    public function test_a_rejected_upload_leaves_nothing_on_disk(): void
    {
        $this->giveTheAdImages(Ad::MAX_IMAGES);

        $this->submitEdit(['images' => [UploadedFile::fake()->image('extra.jpg')]]);

        $this->assertEmpty(
            Storage::disk('public')->allFiles(),
            'فایل آپلودشده با وجود ردشدنِ اعتبارسنجی روی دیسک مانده است.'
        );
    }

    /*
    | چند ویرایش پشت سر هم هم نباید از سقف رد شود - همان سناریویی که
    | در عمل اتفاق افتاد.
    */
    public function test_repeated_edits_cannot_climb_past_the_cap(): void
    {
        $this->giveTheAdImages(8);

        // دو تای اول جا دارند
        $this->submitEdit([
            'images' => [
                UploadedFile::fake()->image('a.jpg'),
                UploadedFile::fake()->image('b.jpg'),
            ],
        ])->assertSessionHasNoErrors();

        $this->approveThePendingEdit();
        $this->assertSame(10, $this->ad->fresh()->images()->count());

        // سومی دیگر جا ندارد
        $this->submitEdit(['images' => [UploadedFile::fake()->image('c.jpg')]])
            ->assertSessionHasErrors('images');
    }

    /*
    | اگر کاربر همان تعداد را برای حذف تیک بزند، افزودن باید مجاز باشد.
    | وگرنه سقف تبدیل می‌شد به «دیگر هیچ‌وقت نمی‌توانی عکس عوض کنی».
    */
    public function test_swapping_images_within_the_cap_is_allowed(): void
    {
        $images = $this->giveTheAdImages(Ad::MAX_IMAGES);

        $this->submitEdit([
            'delete_images' => [$images[0]->id, $images[1]->id],
            'images' => [
                UploadedFile::fake()->image('new-1.jpg'),
                UploadedFile::fake()->image('new-2.jpg'),
            ],
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseCount('ad_edits', 1);
    }

    /*
    | حقه‌ی واضح: یک شناسه را چند بار بفرست تا به‌نظر برسد چند تصویر
    | حذف می‌شود و جا برای تصاویر بیشتری باز شود.
    */
    public function test_repeating_the_same_delete_id_does_not_buy_extra_room(): void
    {
        $images = $this->giveTheAdImages(Ad::MAX_IMAGES);

        $this->submitEdit([
            'delete_images' => [$images[0]->id, $images[0]->id, $images[0]->id],
            'images' => [
                UploadedFile::fake()->image('1.jpg'),
                UploadedFile::fake()->image('2.jpg'),
                UploadedFile::fake()->image('3.jpg'),
            ],
        ])->assertSessionHasErrors('images');
    }

    /*
    | و بیش از ۱۰ فایل در یک درخواست، مستقل از اینکه آگهی خالی باشد.
    */
    public function test_more_than_ten_files_in_one_request_is_refused(): void
    {
        $files = [];

        for ($i = 0; $i <= Ad::MAX_IMAGES; $i++) {
            $files[] = UploadedFile::fake()->image("img-{$i}.jpg");
        }

        $this->submitEdit(['images' => $files])->assertSessionHasErrors('images');
    }

    /*
    |--------------------------------------------------------------------------
    | کمکی
    |--------------------------------------------------------------------------
    */
    private function giveTheAdImages(int $count): array
    {
        $created = [];

        for ($i = 0; $i < $count; $i++) {
            $created[] = AdImage::create([
                'ad_id' => $this->ad->id,
                'path' => "ads/existing-{$i}.jpg",
                'is_primary' => $i === 0,
            ]);
        }

        return $created;
    }

    private function submitEdit(array $extra)
    {
        return $this->actingAs($this->owner)->put(
            route('ad.edit.store', $this->ad),
            array_merge([
                'title' => $this->ad->title,
                'price' => 1000,
                'category_id' => $this->ad->category_id,
                'province_id' => $this->ad->province_id,
                'city_id' => $this->ad->city_id,
                'phone' => '09121234567',
                'address' => 'آدرس',
                'card_number' => str_repeat('3', 24),
            ], $extra)
        );
    }

    private function approveThePendingEdit(): void
    {
        $admin = User::create([
            'name' => 'مدیر', 'username' => 'admin-' . uniqid(),
            'mobile' => '0912' . random_int(1000000, 9999999),
            'password' => 'secret-password',
        ]);
        $admin->forceFill(['is_admin' => true])->save();

        $edit = \App\Models\AdEdit::where('status', 'pending')->firstOrFail();

        $this->actingAs($admin)->post(route('admin.ad-edits.approve', $edit));
    }
}
