<?php

namespace Tests\Feature;

use App\Models\Ad;
use App\Models\AdEdit;
use App\Models\Category;
use App\Models\City;
use App\Models\Province;
use App\Models\ServicePlan;
use App\Models\ServiceSubscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| شماره شبا دیگر از کاربر پرسیده نمی‌شود
|--------------------------------------------------------------------------
|
| این فیلد فقط برای واریز وجهِ فروشِ آنلاین لازم بود. خرید آنلاین
| خاموش است و تماس مستقیم انجام می‌شود، پس یک فیلد ۲۴ رقمیِ اجباری سر
| راه ثبت آگهی مانده بود - برای پولی که اصلاً از این سایت رد نمی‌شود.
|
| کد حذف نشد، خاموش شد: marketplace.collect_card_number. با روشن‌کردن
| یک کلید در .env همه‌چیز برمی‌گردد.
|
| ⚠️ خطرناک‌ترین بخش این تغییر، ویرایش آگهی است.
|
| اگر فقط فیلد از فرم برداشته شود، کنترلر هنوز card_number را merge
| می‌کند و چون فرم چیزی نفرستاده، مقدارش null می‌شود، وارد payload
| می‌رود، و با تأیید ویرایش شماره‌ی ذخیره‌شده پاک می‌شود - بی‌آنکه
| کسی خواسته باشد. تست‌های پایین همین را قفل می‌کنند.
|
*/
class CardNumberIsOptionalTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'name' => 'ارائه‌دهنده', 'username' => 'provider',
            'mobile' => '09120000040', 'password' => 'secret-password',
        ]);

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
    | ثبت آگهی
    |--------------------------------------------------------------------------
    */
    public function test_a_product_ad_is_created_without_any_card_number(): void
    {
        $this->actingAs($this->user)
            ->post(route('ad.store'), $this->payload())
            ->assertSessionHasNoErrors();

        $this->assertSame(1, Ad::count());
        $this->assertNull(Ad::first()->card_number);
    }

    public function test_the_field_is_not_shown_on_the_submit_form(): void
    {
        $this->actingAs($this->user)
            ->get(route('ad.create'))
            ->assertOk()
            ->assertDontSee('name="card_number"', false)
            ->assertDontSee('شماره شبا', false);
    }

    /*
    | و با روشن‌کردن کلید، همه‌چیز برمی‌گردد - وگرنه «برای بعد نگه
    | داشتیم» فقط یک حرف است.
    */
    public function test_turning_the_setting_back_on_brings_the_field_back(): void
    {
        config(['marketplace.collect_card_number' => true]);

        $this->actingAs($this->user)
            ->get(route('ad.create'))
            ->assertOk()
            ->assertSee('name="card_number"', false);

        $this->actingAs($this->user)
            ->post(route('ad.store'), $this->payload())
            ->assertSessionHasErrors('card_number');
    }

    /*
    |--------------------------------------------------------------------------
    | ویرایش آگهی - جایی که خطر بود
    |--------------------------------------------------------------------------
    */
    public function test_editing_an_ad_never_wipes_a_stored_card_number(): void
    {
        $ad = $this->productAd(str_repeat('7', 24));

        $this->actingAs($this->user)
            ->put(route('ad.edit.store', $ad), [
                'title' => 'عنوان تازه',
                'category_id' => $ad->category_id,
                'province_id' => $ad->province_id,
                'city_id' => $ad->city_id,
                'address' => 'آدرس تازه',
                'phone' => '09121234567',
            ])
            ->assertSessionHasNoErrors();

        $edit = AdEdit::first();

        $this->assertNotNull($edit, 'درخواست ویرایش ثبت نشد.');

        /*
        | کلید اصلاً نباید در payload باشد. اگر با مقدار null بیاید،
        | تأیید ویرایش شماره‌ی ذخیره‌شده را پاک می‌کند.
        */
        $this->assertArrayNotHasKey('card_number', $edit->payload);

        // و خودِ آگهی هم هنوز شماره‌اش را دارد.
        $this->assertSame(str_repeat('7', 24), $ad->fresh()->card_number);
    }

    public function test_the_field_is_not_shown_on_the_edit_form(): void
    {
        $ad = $this->productAd(str_repeat('7', 24));

        $this->actingAs($this->user)
            ->get(route('ad.edit', $ad))
            ->assertOk()
            ->assertDontSee('name="card_number"', false)
            ->assertDontSee('شماره شبا', false);
    }

    /*
    | مدیر همچنان باید ببیند - برای واریز وجهِ سفارش‌های قدیمی لازم
    | است و آن فرم تابع این کلید نیست.
    */
    public function test_the_admin_can_still_see_and_edit_it(): void
    {
        $ad = $this->productAd(str_repeat('7', 24));

        $this->user->forceFill(['is_admin' => true])->save();

        $this->actingAs($this->user)
            ->get(route('admin.ads.edit', $ad))
            ->assertOk()
            ->assertSee('name="card_number"', false);
    }

    /*
    |--------------------------------------------------------------------------
    | کمکی
    |--------------------------------------------------------------------------
    */
    private function payload(): array
    {
        [$province, $city, $category] = $this->refs();

        return [
            'type' => 'product',
            'title' => 'آگهی تست',
            'category_id' => $category->id,
            'province_id' => $province->id,
            'city_id' => $city->id,
            'address' => 'آدرس تست',
            'phone' => '09121234567',
        ];
    }

    private function productAd(?string $card): Ad
    {
        [$province, $city, $category] = $this->refs();

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
            'card_number' => $card,
            'status' => 'approved',
        ]);
    }

    private function refs(): array
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

        return [$province, $city, $category];
    }
}
