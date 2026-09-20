<?php

namespace Tests\Feature;

use App\Models\Ad;
use App\Models\AdImage;
use App\Models\Category;
use App\Models\City;
use App\Models\Province;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| گالری تصاویر آگهی و سقف تصویر در فرم‌ها
|--------------------------------------------------------------------------
|
| باگی که این تست‌ها قفلش می‌کنند: کلیک روی بندانگشتی
| style.backgroundImage ظرف را عوض می‌کرد، در حالی که
| .detail-gallery-main img در CSS با عرض و ارتفاع ۱۰۰٪ و
| object-fit:cover کل ظرف را می‌پوشاند. پس عکس پشتِ عکس عوض می‌شد و
| کاربر فقط حاشیه‌ی نارنجی بندانگشتی را می‌دید.
|
| رفتار کلیک خودش در مرورگر بررسی شد؛ اینجا ساختاری تست می‌شود که آن
| رفتار به آن تکیه دارد - چون اگر id یا data-full جابه‌جا شود، اسکریپت
| بی‌صدا از کار می‌افتد و دوباره همان باگ برمی‌گردد.
|
*/
class AdGalleryTest extends TestCase
{
    use RefreshDatabase;

    private Ad $ad;
    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();

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

    /*
    |--------------------------------------------------------------------------
    | گالری
    |--------------------------------------------------------------------------
    */
    public function test_the_main_image_is_the_element_that_carries_the_id(): void
    {
        $this->addImages(2);

        $html = $this->get(route('ad.show', $this->ad->slug))->assertOk()->getContent();

        /*
        | این همان چیزی است که باگ را ساخته بود: id روی ظرف بود، نه
        | روی خودِ تصویر. اسکریپت src را عوض می‌کند، پس id باید روی
        | <img> باشد.
        */
        $this->assertMatchesRegularExpression(
            '/<img[^>]*id="detailMainImg"/',
            $html,
            'شناسه‌ی detailMainImg روی خودِ <img> نیست.'
        );

        $this->assertDoesNotMatchRegularExpression(
            '/<div[^>]*id="detailMainImg"/',
            $html,
            'شناسه هنوز روی ظرف است؛ همان باگ برمی‌گردد.'
        );
    }

    public function test_each_thumbnail_carries_the_full_image_url(): void
    {
        $images = $this->addImages(3);

        $html = $this->get(route('ad.show', $this->ad->slug))->assertOk()->getContent();

        foreach ($images as $image) {
            $this->assertStringContainsString(
                'data-full="' . \Storage::url($image->path) . '"',
                $html
            );
        }
    }

    /*
    | آدرس عکس قبلاً داخل یک onclick و درون template literal چسبانده
    | می‌شد. برگشتن به آن یعنی هر آپاستروف در مسیر فایل کل اسکریپت را
    | می‌شکند.
    */
    public function test_the_thumbnails_do_not_use_inline_onclick(): void
    {
        $this->addImages(2);

        $this->get(route('ad.show', $this->ad->slug))
            ->assertOk()
            ->assertDontSee('onclick=', false);
    }

    public function test_thumbnails_are_buttons_so_the_keyboard_works(): void
    {
        $this->addImages(2);

        $this->get(route('ad.show', $this->ad->slug))
            ->assertOk()
            ->assertSee('<button', false)
            ->assertSee('data-gallery', false);
    }

    /*
    | یک تصویر یعنی چیزی برای جابه‌جا کردن نیست؛ نوار بندانگشتی نباید
    | بیاید.
    */
    public function test_a_single_image_shows_no_thumbnail_strip(): void
    {
        $this->addImages(1);

        /*
        | اسکریپت گالری همیشه در صفحه هست و وقتی نوار نباشد بی‌صدا
        | برمی‌گردد. پس آنچه نباید دیده شود، خودِ نوار است.
        */
        $this->get(route('ad.show', $this->ad->slug))
            ->assertOk()
            ->assertDontSee('class="detail-thumbs"', false);
    }

    /*
    |--------------------------------------------------------------------------
    | سقف تصویر، آنجا که کاربر می‌بیند
    |--------------------------------------------------------------------------
    */
    public function test_the_submit_form_states_the_limit(): void
    {
        $this->actingAs($this->owner)
            ->get(route('ad.create'))
            ->assertOk()
            ->assertSee('حداکثر ' . Ad::MAX_IMAGES . ' تصویر', false)
            ->assertSee('data-max-images="' . Ad::MAX_IMAGES . '"', false);
    }

    /*
    | فرم ویرایش باید «باقی‌مانده» را بگوید، نه سقف کل - وگرنه کاربر
    | ۱۰ تا انتخاب می‌کند و بعد پیام خطا می‌گیرد.
    */
    public function test_the_edit_form_counts_down_the_remaining_slots(): void
    {
        $this->addImages(4);

        $this->actingAs($this->owner)
            ->get(route('ad.edit', $this->ad))
            ->assertOk()
            ->assertSee('data-images-remaining="' . (Ad::MAX_IMAGES - 4) . '"', false)
            ->assertSee((Ad::MAX_IMAGES - 4) . ' تصویر دیگر', false);
    }

    public function test_a_full_ad_is_told_to_free_up_room_first(): void
    {
        $this->addImages(Ad::MAX_IMAGES);

        $this->actingAs($this->owner)
            ->get(route('ad.edit', $this->ad))
            ->assertOk()
            ->assertSee('data-images-remaining="0"', false)
            ->assertSee('به سقف', false);
    }

    /*
    |--------------------------------------------------------------------------
    | کمکی
    |--------------------------------------------------------------------------
    */
    private function addImages(int $count): array
    {
        $created = [];

        for ($i = 0; $i < $count; $i++) {
            $created[] = AdImage::create([
                'ad_id' => $this->ad->id,
                'path' => "ads/image-{$i}.jpg",
                'is_primary' => $i === 0,
            ]);
        }

        return $created;
    }
}
