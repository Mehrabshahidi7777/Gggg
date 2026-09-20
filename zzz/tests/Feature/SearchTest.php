<?php

namespace Tests\Feature;

use App\Models\Ad;
use App\Models\Category;
use App\Models\City;
use App\Models\Province;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| جست‌وجو
|--------------------------------------------------------------------------
|
| ⚠️ محدودیتی که باید صریح گفته شود: این تست‌ها روی SQLite اجرا می‌شوند،
| که MATCH ... AGAINST ندارد. پس مسیر FULLTEXT اینجا اجرا نمی‌شود و
| همه‌ی این تست‌ها در عمل مسیر LIKE را می‌سنجند.
|
| ارزششان این است که ثابت می‌کنند رفتارِ موجود با این تغییر خراب نشده -
| یعنی همان چیزی که کاربر امروز می‌بیند، فردا هم می‌بیند. خودِ مسیر
| FULLTEXT فقط روی MySQLِ واقعی و بعد از ایمپورت فایل SQL قابل آزمایش
| است.
|
*/
class SearchTest extends TestCase
{
    use RefreshDatabase;

    private User $seller;
    private Category $productCategory;
    private Category $serviceCategory;
    private Province $province;
    private City $city;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seller = User::create([
            'name' => 'فروشنده', 'username' => 'seller',
            'mobile' => '09120000001', 'password' => 'secret-password',
        ]);

        $this->province = Province::create(['name' => 'تهران', 'slug' => 'tehran']);
        $this->city = City::create(['name' => 'تهران', 'slug' => 'tehran', 'province_id' => $this->province->id]);

        $this->productCategory = Category::create(['name' => 'مصالح پایه', 'slug' => 'masaleh', 'type' => 'product', 'is_active' => true]);
        $this->serviceCategory = Category::create(['name' => 'برق ساختمان', 'slug' => 'bargh', 'type' => 'service', 'is_active' => true]);
    }

    /*
    |--------------------------------------------------------------------------
    | رفتار پایه - نباید عوض شده باشد
    |--------------------------------------------------------------------------
    */
    public function test_a_product_is_found_by_its_title(): void
    {
        $this->makeAd('product', 'سیمان تیپ ۲ تهران');

        $this->get(route('search', ['search' => 'سیمان']))
            ->assertOk()
            ->assertSee('سیمان تیپ ۲ تهران', false);
    }

    public function test_a_service_is_found_by_its_title(): void
    {
        $this->makeAd('service', 'اجرای برق ساختمان');

        $this->get(route('search', ['search' => 'برق']))
            ->assertOk()
            ->assertSee('اجرای برق ساختمان', false);
    }

    public function test_an_ad_is_found_by_its_description(): void
    {
        $this->makeAd('product', 'محصول الف', ['description' => 'مناسب برای سقف شیبدار']);

        $this->get(route('search', ['search' => 'شیبدار']))
            ->assertOk()
            ->assertSee('محصول الف', false);
    }

    public function test_a_product_is_found_by_its_brand(): void
    {
        $this->makeAd('product', 'محصول ب', ['brand' => 'آبیک']);

        $this->get(route('search', ['search' => 'آبیک']))
            ->assertOk()
            ->assertSee('محصول ب', false);
    }

    public function test_an_ad_is_found_by_its_category_name(): void
    {
        $this->makeAd('product', 'محصول ج');

        $this->get(route('search', ['search' => 'مصالح']))
            ->assertOk()
            ->assertSee('محصول ج', false);
    }

    /*
    | زیررشته‌ی وسط کلمه. این دقیقاً همان حالتی است که FULLTEXT به
    | تنهایی از دست می‌دهد و برای همین عقب‌نشینی به LIKE گذاشته شده.
    */
    public function test_a_substring_from_the_middle_of_a_word_still_matches(): void
    {
        $this->makeAd('product', 'سیمان پرتلند');

        $this->get(route('search', ['search' => 'یمان']))
            ->assertOk()
            ->assertSee('سیمان پرتلند', false);
    }

    /*
    | عبارت کوتاه‌تر از حد ایندکس MySQL. باید همیشه از LIKE برود.
    */
    public function test_a_two_letter_term_still_works(): void
    {
        $this->makeAd('product', 'در و پنجره آلومینیومی');

        $this->assertFalse(Ad::fullTextIsUsable('در'));

        $this->get(route('search', ['search' => 'در']))
            ->assertOk()
            ->assertSee('در و پنجره آلومینیومی', false);
    }

    /*
    |--------------------------------------------------------------------------
    | چیزهایی که نباید دیده شوند
    |--------------------------------------------------------------------------
    */
    public function test_a_pending_ad_never_appears_in_search(): void
    {
        $ad = $this->makeAd('product', 'آگهی-در-انتظار-تأیید');
        $ad->update(['status' => 'pending']);

        $this->get(route('search', ['search' => 'انتظار']))
            ->assertOk()
            ->assertDontSee('آگهی-در-انتظار-تأیید', false);
    }

    public function test_a_suspended_ad_never_appears_in_search(): void
    {
        $ad = $this->makeAd('product', 'آگهی-تعلیق‌شده');
        $ad->update(['is_suspended' => true, 'suspended_at' => now()]);

        $this->get(route('search', ['search' => 'تعلیق']))
            ->assertOk()
            ->assertDontSee('آگهی-تعلیق‌شده', false);
    }

    public function test_an_empty_term_goes_home(): void
    {
        $this->get(route('search', ['search' => '   ']))
            ->assertRedirect(route('home'));
    }

    /*
    |--------------------------------------------------------------------------
    | ساختِ عبارت boolean
    |--------------------------------------------------------------------------
    |
    | این‌ها بدون MySQL هم معنا دارند، چون فقط رشته می‌سازند.
    */
    public function test_each_word_becomes_required_with_a_prefix_wildcard(): void
    {
        $this->assertSame('+سیمان*', Ad::booleanQueryFor('سیمان'));
        $this->assertSame('+سیمان* +تهران*', Ad::booleanQueryFor('سیمان تهران'));
    }

    public function test_extra_whitespace_does_not_create_empty_tokens(): void
    {
        $this->assertSame('+سیمان* +تهران*', Ad::booleanQueryFor("  سیمان \n\t تهران  "));
    }

    /*
    | کاربر نباید بتواند عملگرهای MySQL را تزریق کند - نه برای دستکاری
    | نتیجه و نه برای شکستن کوئری با یک پرانتزِ باز.
    */
    public function test_mysql_boolean_operators_are_stripped_from_user_input(): void
    {
        foreach (['-سیمان', '+سیمان', '~سیمان', '"سیمان', 'سیمان*', '(سیمان', '@سیمان'] as $input) {
            $this->assertSame(
                '+سیمان*',
                Ad::booleanQueryFor($input),
                "عملگر در «{$input}» پاک نشده است."
            );
        }
    }

    public function test_a_term_made_only_of_operators_produces_nothing(): void
    {
        $this->assertSame('', Ad::booleanQueryFor('--- +++ ***'));
    }

    /*
    |--------------------------------------------------------------------------
    | انتخاب مسیر
    |--------------------------------------------------------------------------
    */
    public function test_full_text_is_never_used_on_a_driver_without_it(): void
    {
        // این مجموعه روی SQLite اجرا می‌شود.
        $this->assertSame('sqlite', \DB::connection()->getDriverName());
        $this->assertFalse(Ad::fullTextIsUsable('سیمان'));
    }

    public function test_a_short_word_in_a_longer_phrase_also_forces_the_like_path(): void
    {
        // «در» کوتاه است، پس کل عبارت باید به LIKE بیفتد.
        $this->assertFalse(Ad::fullTextIsUsable('سیمان در تهران'));
    }

    /*
    |--------------------------------------------------------------------------
    | کمکی
    |--------------------------------------------------------------------------
    */
    private function makeAd(string $type, string $title, array $extra = []): Ad
    {
        return Ad::create(array_merge([
            'user_id' => $this->seller->id,
            'category_id' => $type === 'product'
                ? $this->productCategory->id
                : $this->serviceCategory->id,
            'province_id' => $this->province->id,
            'city_id' => $this->city->id,
            'type' => $type,
            'title' => $title,
            'price' => 1000,
            'address' => 'آدرس',
            'phone' => '09121234567',
            'status' => 'approved',
        ], $extra));
    }
}
