<?php

namespace Tests\Feature;

use App\Models\Ad;
use App\Models\AdImage;
use App\Models\Category;
use App\Models\City;
use App\Models\Province;
use App\Models\ServicePlan;
use App\Models\ServiceSubscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| سقف تصویر نباید انتخاب کاربر را بی‌صدا دور بیندازد
|--------------------------------------------------------------------------
|
| باگی که این تست‌ها قفلش می‌کنند:
|
| اسکریپت سقف تصویر، وقتی کاربر بیشتر از سقف انتخاب می‌کرد،
| input.value = '' می‌زد - یعنی انتخابش را پاک می‌کرد - و فقط یک خط
| راهنمای کوچک می‌نوشت.
|
| کاربر آن خط را نمی‌دید، «ثبت آگهی» را می‌زد، و آگهی با *صفر* تصویر
| ساخته می‌شد. بدتر از آن: چون دیگر هیچ فایلی در فرم نبود، اعتبارسنجی
| سرور هم چیزی برای ایراد گرفتن نداشت، پس هیچ خطایی هم نشان داده
| نمی‌شد. کاربر فکر می‌کرد عکس‌هایش آپلود شده‌اند.
|
| ⚠️ رفتار مرورگر جداگانه در کرومیوم بررسی شد (۱۹ بررسی): پاک نشدن
| انتخاب، شمارش ساده موقع انتخاب، پیام سرِ ثبت با تعداد دقیقِ اضافه،
| و زنده بودن ظرفیت در فرم ویرایش وقتی کاربر تصویری را برای حذف تیک
| می‌زند.
|
| آنچه اینجا تست می‌شود دو چیز است که آن رفتار به آنها تکیه دارد:
| قلّاب‌های مارک‌آپ، و اینکه خودِ سرور - که حرف آخر را می‌زند - آگهی
| را با تصویرِ بیش از حد نسازد.
|
*/
class ImageLimitNeverDropsFilesTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->user = User::create([
            'name' => 'کاربر', 'username' => 'sazande',
            'mobile' => '09120000030', 'password' => 'secret-password',
        ]);

        /*
        | ثبت آگهی محصول اشتراک فعال می‌خواهد، وگرنه کاربر به صفحه‌ی
        | پلن‌ها فرستاده می‌شود و هیچ‌وقت به بررسی تصویرها نمی‌رسیم.
        |
        | تست‌های «بیش از سقف» بدون این هم قرمز می‌شدند، چون
        | اعتبارسنجی پیش از این بررسی اجرا می‌شود - ولی تستِ «درست
        | روی سقف» بدونش بی‌معنی بود.
        */
        $plan = ServicePlan::create([
            'type' => 'product', 'months' => 1,
            'title' => 'یک ماهه', 'price' => 150000, 'is_active' => true,
        ]);

        ServiceSubscription::create([
            'type' => 'product',
            'user_id' => $this->user->id,
            'service_plan_id' => $plan->id,
            'amount' => 150000,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addMonth(),
            'paid_at' => now()->subDay(),
            'status' => 'active',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | سرور: حرف آخر
    |--------------------------------------------------------------------------
    |
    | مهم‌ترین تست این فایل. دقیقاً همان چیزی که کاربر گزارش کرد:
    | آگهی ساخته شد ولی صفر تصویر داشت. حالا اصلاً ساخته نمی‌شود.
    */
    public function test_too_many_images_is_rejected_and_no_ad_is_created(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('ad.store'), $this->adPayload(Ad::MAX_IMAGES + 5));

        $response->assertSessionHasErrors('images');

        $this->assertSame(0, Ad::count(), 'آگهی نباید ساخته می‌شد.');
        $this->assertSame(0, AdImage::count());
    }

    public function test_the_rejection_says_what_the_limit_is(): void
    {
        $this->actingAs($this->user)
            ->post(route('ad.store'), $this->adPayload(Ad::MAX_IMAGES + 1))
            ->assertSessionHasErrors(['images' => 'حداکثر ۱۰ تصویر می‌توانید انتخاب کنید.']);
    }

    /*
    | و درست روی سقف باید قبول شود - وگرنه یک خطای «یکی بیشتر» بی‌صدا
    | سقف را ۹ می‌کرد.
    */
    public function test_exactly_the_limit_is_accepted_with_every_image_kept(): void
    {
        $this->actingAs($this->user)
            ->post(route('ad.store'), $this->adPayload(Ad::MAX_IMAGES))
            ->assertSessionHasNoErrors();

        $this->assertSame(1, Ad::count());
        $this->assertSame(Ad::MAX_IMAGES, AdImage::count());
    }

    /*
    |--------------------------------------------------------------------------
    | قلّاب‌های مارک‌آپ
    |--------------------------------------------------------------------------
    |
    | اسکریپت بدون اینها بی‌صدا از کار می‌افتد.
    */
    public function test_the_submit_form_carries_the_limit_and_a_place_for_the_message(): void
    {
        $html = $this->actingAs($this->user)->get(route('ad.create'))->assertOk()->getContent();

        $this->assertStringContainsString('data-max-images="' . Ad::MAX_IMAGES . '"', $html);
        $this->assertStringContainsString('data-images-note="#images-note"', $html);
        $this->assertStringContainsString('id="images-note"', $html);
    }

    public function test_the_edit_form_reports_its_remaining_room(): void
    {
        $ad = $this->approvedAd();

        foreach (range(1, 4) as $i) {
            AdImage::create(['ad_id' => $ad->id, 'path' => "ads/{$i}.jpg", 'is_primary' => $i === 1]);
        }

        $html = $this->actingAs($this->user)->get(route('ad.edit', $ad))->assertOk()->getContent();

        $this->assertStringContainsString('data-images-remaining="' . (Ad::MAX_IMAGES - 4) . '"', $html);

        /*
        | سقف کل هم لازم است، نه فقط باقی‌مانده: اسکریپت با تیک‌خوردن
        | چک‌باکس‌های حذف، ظرفیت را زنده حساب می‌کند و برای آن به عدد
        | کل نیاز دارد.
        */
        $this->assertStringContainsString('data-max-images="' . Ad::MAX_IMAGES . '"', $html);
    }

    /*
    | خودِ خطِ باگ. برگشتنش یعنی برگشتن همان آگهیِ بی‌تصویر.
    */
    public function test_the_script_never_clears_the_file_input(): void
    {
        $html = $this->actingAs($this->user)->get(route('ad.create'))->assertOk()->getContent();

        $this->assertDoesNotMatchRegularExpression(
            "/input\.value\s*=\s*''/",
            $html,
            'اسکریپت دوباره انتخاب کاربر را پاک می‌کند؛ آگهی با صفر تصویر ثبت خواهد شد.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | کمکی
    |--------------------------------------------------------------------------
    */
    private function adPayload(int $imageCount): array
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

        return [
            'type' => 'product',
            'title' => 'آگهی تست',
            'category_id' => $category->id,
            'province_id' => $province->id,
            'city_id' => $city->id,
            'address' => 'آدرس تست',
            'phone' => '09121234567',
            'card_number' => str_repeat('3', 24),
            'images' => array_map(
                fn ($i) => UploadedFile::fake()->image("pic-{$i}.jpg", 40, 40),
                range(1, $imageCount)
            ),
        ];
    }

    private function approvedAd(): Ad
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
            'user_id' => $this->user->id,
            'category_id' => $category->id,
            'province_id' => $province->id,
            'city_id' => $city->id,
            'type' => 'product',
            'title' => 'آگهی موجود',
            'price' => 1000,
            'address' => 'آدرس',
            'phone' => '09121234567',
            'status' => 'approved',
        ]);
    }
}
