<?php

namespace Tests\Feature;

use App\Models\Ad;
use App\Models\AdContactReveal;
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
| آمار پنل ارائه‌دهنده
|--------------------------------------------------------------------------
|
| ارائه‌دهنده ماهی ۱۵۰ هزار تومان می‌دهد و تا امروز فقط یک عددِ خالی
| می‌دید: «تماس‌های این ماه: ۴۷».
|
| آن عدد دو چیز را نمی‌گفت، و هر دو همان سؤالی‌اند که تصمیم تمدید را
| می‌سازند:
|
|   ۱. اوضاع بهتر شده یا بدتر؟ (مقایسه با ماه قبل)
|   ۲. کدام آگهی این تماس‌ها را آورده؟
|
| ⚠️ بازه‌ی مقایسه عمداً «ماه تقویمیِ قبل» است، نه «۳۰ روز گذشته» -
| تا با «این ماه» هم‌جنس باشد و عدد گمراه‌کننده نشود.
|
*/
class ProviderPanelStatsTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Ad $strong;
    private Ad $weak;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'name' => 'ارائه‌دهنده', 'username' => 'provider',
            'mobile' => '09120000050', 'password' => 'secret-password',
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
            'starts_at' => now()->subDays(10),
            'ends_at' => now()->addMonth(),
            'paid_at' => now()->subDays(10),
            'status' => 'active',
        ]);

        $this->strong = $this->ad('سیمان تیپ دو', 200);
        $this->weak = $this->ad('آجر فشاری', 100);
    }

    /*
    |--------------------------------------------------------------------------
    | مقایسه با ماه قبل
    |--------------------------------------------------------------------------
    */
    public function test_a_better_month_is_reported_as_growth(): void
    {
        $this->reveals($this->strong, 2, lastMonth: true);
        $this->reveals($this->strong, 5);

        $this->panel()
            ->assertViewHas('leadsThisMonth', 5)
            ->assertViewHas('leadsLastMonth', 2)
            ->assertSee('3 بیشتر از ماه قبل', false);
    }

    public function test_a_worse_month_is_reported_honestly(): void
    {
        $this->reveals($this->strong, 6, lastMonth: true);
        $this->reveals($this->strong, 1);

        $this->panel()->assertSee('5 کمتر از ماه قبل', false);
    }

    public function test_an_unchanged_month_says_so(): void
    {
        $this->reveals($this->strong, 3, lastMonth: true);
        $this->reveals($this->strong, 3);

        $this->panel()->assertSee('بدون تغییر نسبت به ماه قبل', false);
    }

    /*
    | ماه اولِ یک ارائه‌دهنده هیچ «ماه قبلی» ندارد. نوشتن «۱۰۰٪ رشد»
    | یا «۵ بیشتر از ماه قبل» آنجا بی‌معنی است، پس چیزی گفته نمی‌شود.
    */
    public function test_a_brand_new_provider_is_not_shown_a_meaningless_comparison(): void
    {
        $this->panel()
            ->assertViewHas('leadsLastMonth', 0)
            ->assertDontSee('بیشتر از ماه قبل', false)
            ->assertDontSee('بدون تغییر نسبت به ماه قبل', false);
    }

    /*
    | تماس‌های ماه قبل نباید در عددِ «این ماه» بیایند - وگرنه عدد
    | همیشه بالا می‌رود و هیچ‌وقت کم نمی‌شود.
    */
    public function test_last_months_contacts_stay_out_of_this_months_number(): void
    {
        $this->reveals($this->strong, 4, lastMonth: true);

        $this->panel()
            ->assertViewHas('leadsThisMonth', 0)
            ->assertViewHas('leadsLastMonth', 4)
            ->assertViewHas('leadsTotal', 4);
    }

    /*
    |--------------------------------------------------------------------------
    | کدام آگهی کار می‌کند
    |--------------------------------------------------------------------------
    */
    public function test_the_best_performing_ad_is_marked(): void
    {
        $this->reveals($this->strong, 5);
        $this->reveals($this->weak, 1);

        $this->panel()
            ->assertViewHas('bestPerformerId', $this->strong->id)
            ->assertSee('بهترین عملکرد', false);
    }

    /*
    | وقتی هیچ تماسی نبوده، هیچ آگهی‌ای «بهترین» نیست. نشان‌دادن
    | نشانِ بهترین روی آگهی‌ای با صفر تماس، دروغ تشویقی است.
    */
    public function test_nothing_is_called_best_when_no_one_has_called(): void
    {
        $this->panel()
            ->assertViewHas('bestPerformerId', null)
            ->assertDontSee('بهترین عملکرد', false);
    }

    public function test_each_ad_shows_its_own_numbers(): void
    {
        $this->reveals($this->strong, 3);

        $html = $this->panel()->getContent();

        $this->assertStringContainsString('تماس این ماه', $html);
        $this->assertStringContainsString('بازدید', $html);
        $this->assertStringContainsString('نرخ تبدیل', $html);
    }

    /*
    |--------------------------------------------------------------------------
    | بازدید و نرخ تبدیل
    |--------------------------------------------------------------------------
    */
    public function test_views_are_totalled_across_the_ads(): void
    {
        $this->panel()->assertViewHas('viewsTotal', 300);
    }

    /*
    | تقسیم بر صفر. آگهی تازه هیچ بازدیدی ندارد و نرخ تبدیل برایش
    | تعریف نشده است - نه صفر درصد.
    */
    public function test_an_ad_with_no_views_does_not_break_the_page(): void
    {
        Ad::query()->update(['views_count' => 0]);

        $this->panel()->assertOk()->assertDontSee('نرخ تبدیل', false);
    }

    /*
    |--------------------------------------------------------------------------
    | مرزها
    |--------------------------------------------------------------------------
    |
    | آمار یک ارائه‌دهنده نباید در پنل دیگری دیده شود.
    */
    public function test_another_providers_numbers_are_not_counted(): void
    {
        $stranger = User::create([
            'name' => 'دیگری', 'username' => 'stranger',
            'mobile' => '09120000051', 'password' => 'secret-password',
        ]);

        $strangerAd = Ad::create([
            'user_id' => $stranger->id,
            'category_id' => $this->strong->category_id,
            'province_id' => $this->strong->province_id,
            'city_id' => $this->strong->city_id,
            'type' => 'product',
            'title' => 'آگهی دیگری',
            'price' => 1,
            'address' => 'آدرس',
            'phone' => '09129999999',
            'status' => 'approved',
            'views_count' => 5000,
        ]);

        $this->reveals($strangerAd, 99);

        $this->panel()
            ->assertViewHas('leadsThisMonth', 0)
            ->assertViewHas('viewsTotal', 300);
    }

    /*
    |--------------------------------------------------------------------------
    | کمکی
    |--------------------------------------------------------------------------
    */
    private function panel()
    {
        return $this->actingAs($this->user)->get(route('product.panel'))->assertOk();
    }

    private function ad(string $title, int $views): Ad
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
            'title' => $title,
            'price' => 1000,
            'address' => 'آدرس',
            'phone' => '09121234567',
            'status' => 'approved',
            'views_count' => $views,
        ]);
    }

    /*
    | کلید یکتای جدول «این کاربر، این آگهی، این روز» است، پس برای
    | چند تماس باید روزها فرق کنند.
    */
    private function reveals(Ad $ad, int $count, bool $lastMonth = false): void
    {
        $anchor = $lastMonth
            ? now()->subMonthNoOverflow()->startOfMonth()->addDay()
            : now()->startOfMonth()->addDay();

        foreach (range(1, $count) as $i) {
            $day = $anchor->copy()->addDays($i - 1);

            /*
            | created_at در $fillable مدل نیست (آن جدول فقط created_at
            | دارد و هرگز ویرایش نمی‌شود)، پس create() نادیده‌اش
            | می‌گیرد و همه‌ی ردیف‌ها «الان» می‌شوند. اولین بار همین
            | اتفاق افتاد و تستِ «ماه قبل» بی‌معنی شد.
            */
            AdContactReveal::create([
                'ad_id' => $ad->id,
                'user_id' => null,
                'ip_hash' => str_repeat((string) ($i % 10), 64),
                'revealed_on' => $day->toDateString(),
            ])->forceFill(['created_at' => $day])->save();
        }
    }
}
