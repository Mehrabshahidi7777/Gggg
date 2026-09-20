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
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| پاک‌سازی شبانه نباید تصاویرِ در انتظار تأیید را ببلعد
|--------------------------------------------------------------------------
|
| تصویری که ارائه‌دهنده در فرم ویرایش می‌فرستد بلافاصله روی دیسک
| می‌نشیند، ولی تا تأیید مدیر هیچ ردیفی در ad_images ندارد؛ تنها
| مرجعش ستون added_images در ad_edits است.
|
| این منبع در فهرست «فایل‌های در استفاده» جا افتاده بود. اگر مدیر
| درخواست را تا ۲۴ ساعت بررسی نمی‌کرد، پاک‌سازی شبانه فایل‌ها را حذف
| می‌کرد و تأییدِ بعدی آگهی را با تصویرهای شکسته می‌ساخت.
|
| فایل‌ها عمداً با زمانِ گذشته ساخته می‌شوند، چون این دستور آپلودِ
| کمتر از ۲۴ ساعت را دست نمی‌زند و بدون این کار تست بی‌معنا می‌شد.
|
*/
class CleanupKeepsPendingEditImagesTest extends TestCase
{
    use RefreshDatabase;

    private Ad $ad;
    private User $owner;

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
            'status' => 'approved',
        ]);
    }

    public function test_an_image_waiting_for_approval_survives_the_nightly_cleanup(): void
    {
        $path = $this->anOldFile('ads/waiting-for-approval.jpg');

        AdEdit::create([
            'ad_id' => $this->ad->id,
            'user_id' => $this->owner->id,
            'payload' => ['title' => 'عنوان تازه'],
            'original' => ['title' => 'آگهی تست'],
            'added_images' => [$path],
            'status' => 'pending',
        ]);

        $this->artisan('sazmat:cleanup')->assertSuccessful();

        Storage::disk('public')->assertExists($path);
    }

    /*
    | و نقطه‌ی مقابل، وگرنه تست بالا با «هیچ‌وقت چیزی را پاک نکن» هم
    | سبز می‌شد: فایلی که هیچ مرجعی ندارد باید برود.
    */
    public function test_a_genuinely_orphaned_file_is_still_deleted(): void
    {
        $path = $this->anOldFile('ads/nobody-references-this.jpg');

        $this->artisan('sazmat:cleanup')->assertSuccessful();

        Storage::disk('public')->assertMissing($path);
    }

    public function test_an_image_attached_to_the_ad_survives(): void
    {
        $path = $this->anOldFile('ads/already-on-the-ad.jpg');

        AdImage::create(['ad_id' => $this->ad->id, 'path' => $path, 'is_primary' => true]);

        $this->artisan('sazmat:cleanup')->assertSuccessful();

        Storage::disk('public')->assertExists($path);
    }

    /*
    | یک درخواستِ بررسی‌شده دیگر فایل‌هایش را نگه نمی‌دارد: تأییدشده
    | آنها را به ad_images منتقل کرده و ردشده همان لحظه حذفشان کرده.
    | پس added_imagesِ یک درخواستِ تأییدشده نباید فایلِ بی‌مرجع را زنده
    | نگه دارد.
    */
    public function test_a_reviewed_edit_does_not_keep_stale_files_alive(): void
    {
        $path = $this->anOldFile('ads/left-over-from-an-approved-edit.jpg');

        AdEdit::create([
            'ad_id' => $this->ad->id,
            'user_id' => $this->owner->id,
            'payload' => ['title' => 'عنوان'],
            'original' => ['title' => 'آگهی تست'],
            'added_images' => [$path],
            'status' => 'approved',
        ]);

        $this->artisan('sazmat:cleanup')->assertSuccessful();

        Storage::disk('public')->assertMissing($path);
    }

    /*
    | فایلی که همین الان آپلود شده - چه مرجع داشته باشد چه نه - نباید
    | حذف شود، وگرنه آپلودِ در جریان قربانی می‌شود.
    */
    public function test_a_freshly_uploaded_file_is_never_touched(): void
    {
        Storage::disk('public')->put('ads/just-uploaded.jpg', 'x');

        $this->artisan('sazmat:cleanup')->assertSuccessful();

        Storage::disk('public')->assertExists('ads/just-uploaded.jpg');
    }

    /*
    |--------------------------------------------------------------------------
    | کمکی: فایلی که به‌اندازه‌ی کافی قدیمی است تا پاک‌سازی نگاهش کند
    |--------------------------------------------------------------------------
    */
    private function anOldFile(string $path): string
    {
        $disk = Storage::disk('public');
        $disk->put($path, 'x');

        touch($disk->path($path), now()->subDays(3)->getTimestamp());

        return $path;
    }
}
