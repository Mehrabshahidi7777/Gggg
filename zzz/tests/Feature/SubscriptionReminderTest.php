<?php

namespace Tests\Feature;

use App\Models\Ad;
use App\Models\Category;
use App\Models\City;
use App\Models\Province;
use App\Models\ServicePlan;
use App\Models\ServiceSubscription;
use App\Models\User;
use App\Services\AmootSmsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class SubscriptionReminderTest extends TestCase
{
    use RefreshDatabase;

    private ServicePlan $plan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->plan = ServicePlan::create([
            'type' => 'service', 'months' => 1, 'title' => 'یک ماهه',
            'price' => 150000, 'is_active' => true, 'sort_order' => 1,
        ]);
    }

    private function user(string $mobile = '09121234567'): User
    {
        return User::create([
            'name' => 'کاربر',
            'username' => 'u' . $mobile,
            'mobile' => $mobile,
            'password' => 'secret-password',
        ]);
    }

    private function subscription(
        User $user,
        $endsAt,
        string $status = 'active',
        string $type = 'service'
    ): ServiceSubscription {
        $plan = $type === 'service' ? $this->plan : ServicePlan::firstOrCreate(
            ['type' => 'product', 'months' => 1],
            ['title' => 'یک ماهه', 'price' => 150000, 'is_active' => true, 'sort_order' => 1]
        );

        return ServiceSubscription::create([
            'type' => $type,
            'user_id' => $user->id,
            'service_plan_id' => $plan->id,
            'amount' => 150000,
            'starts_at' => now()->subMonth(),
            'ends_at' => $endsAt,
            'status' => $status,
        ]);
    }

    /* کاربری که با ایمیل ثبت‌نام کرده: هیچ شماره‌ای ندارد. */
    private function emailUser(): User
    {
        return User::create([
            'name' => 'بدون موبایل',
            'username' => 'nomobile',
            'email' => 'x@example.com',
            'password' => 'secret-password',
        ]);
    }

    private function ad(User $user, string $phone, string $type = 'service'): Ad
    {
        $province = Province::firstOrCreate(['slug' => 'tehran'], ['name' => 'تهران']);
        $city = City::firstOrCreate(
            ['slug' => 'tehran', 'province_id' => $province->id],
            ['name' => 'تهران']
        );
        $category = Category::firstOrCreate(
            ['slug' => 'cat-' . $type],
            ['name' => 'دسته', 'type' => $type, 'is_active' => true]
        );

        return Ad::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'province_id' => $province->id,
            'city_id' => $city->id,
            'type' => $type,
            'title' => 'آگهی آزمایشی',
            'price' => 1000,
            'address' => 'آدرس',
            'phone' => $phone,
            'status' => 'approved',
        ]);
    }

    /* ارسال پیامک را جعل می‌کنیم تا تست به سامانه‌ی واقعی وصل نشود. */
    private function fakeSms(): Mockery\MockInterface
    {
        $mock = Mockery::mock(AmootSmsService::class);
        $this->app->instance(AmootSmsService::class, $mock);

        return $mock;
    }

    public function test_seven_day_reminder_is_sent_once(): void
    {
        $subscription = $this->subscription($this->user(), now()->addDays(5));

        $sms = $this->fakeSms();
        $sms->shouldReceive('sendRenewalReminder')->once();

        $this->artisan('sazmat:subscription-reminders')->assertSuccessful();

        $this->assertNotNull($subscription->fresh()->reminder_7d_sent_at);

        // اجرای دوباره نباید پیامک دیگری بفرستد
        $this->artisan('sazmat:subscription-reminders')->assertSuccessful();
    }

    public function test_expiry_reminder_is_sent_for_finished_subscription(): void
    {
        $subscription = $this->subscription($this->user(), now()->subHour(), 'expired');

        $sms = $this->fakeSms();
        $sms->shouldReceive('sendRenewalReminder')->once();

        $this->artisan('sazmat:subscription-reminders')->assertSuccessful();

        $this->assertNotNull($subscription->fresh()->reminder_expired_sent_at);
    }

    /*
    |--------------------------------------------------------------------------
    | یک پترن، سه مرحله
    |--------------------------------------------------------------------------
    |
    | ⚠️ متغیر سوم یک عبارت است، نه عدد - و همین است که یک پترن را
    | برای هر سه مرحله کافی می‌کند.
    |
    | اگر عدد روز می‌رفت، مرحله‌ی آخر پیامکِ «تا ۰ روز دیگر تمام
    | می‌شود» می‌داد: هم غلط، هم دقیقاً برعکسِ کاری که باید بکند.
    | کاربر باید بفهمد آگهی‌هایش همین حالا تعلیق شده‌اند.
    */
    public function test_the_expired_stage_does_not_say_zero_days(): void
    {
        $this->subscription($this->user(), now()->subHour(), 'expired');

        $sms = $this->fakeSms();
        $sms->shouldReceive('sendRenewalReminder')->once()->with(
            '09121234567',
            Mockery::any(),
            Mockery::on(fn ($v) => $v[1] === 'تمام شد؛ آگهی‌ها تعلیق شدند')
        );

        $this->artisan('sazmat:subscription-reminders')->assertSuccessful();
    }

    /*
    | ⚠️ اشتراک ۵ روز دیگر تمام می‌شود، پس پیامک باید «۵» بگوید نه
    | «۷». عددِ ۷ سقفِ بازه‌ی مرحله است، نه چیزی که برای این کاربر
    | درست باشد - و پیامکی که عدد غلط بدهد بدتر از نفرستادن است.
    */
    public function test_the_reminder_counts_the_real_days_not_the_stage(): void
    {
        $this->subscription($this->user(), now()->addDays(5));

        $sms = $this->fakeSms();
        $sms->shouldReceive('sendRenewalReminder')->once()->with(
            '09121234567',
            Mockery::any(),
            Mockery::on(fn ($v) => count($v) === 2
                && $v[0] === 'خدمات'
                && $v[1] === 'تا 5 روز دیگر تمام می‌شود')
        );

        $this->artisan('sazmat:subscription-reminders')->assertSuccessful();
    }

    /*
    | و همان اشتراک نباید در یک اجرا دو پیامک بگیرد.
    |
    | تا پیش از این، اشتراکی که ۱۲ ساعت دیگر تمام می‌شد هم در بازه‌ی
    | ۷ روز می‌افتاد و هم در بازه‌ی ۱ روز: دو پیامک پشت سر هم، با دو
    | متن متفاوت، و دو برابر هزینه.
    */
    public function test_one_subscription_never_gets_two_messages_in_one_run(): void
    {
        $this->subscription($this->user(), now()->addHours(12));

        $sms = $this->fakeSms();
        $sms->shouldReceive('sendRenewalReminder')->once();

        $this->artisan('sazmat:subscription-reminders')->assertSuccessful();
    }

    /* «تا ۱ روز دیگر» فارسی نیست؛ «فردا» است. */
    public function test_the_last_day_says_tomorrow(): void
    {
        $this->subscription($this->user(), now()->addHours(12));

        $sms = $this->fakeSms();
        $sms->shouldReceive('sendRenewalReminder')->once()->with(
            '09121234567',
            Mockery::any(),
            Mockery::on(fn ($v) => $v[1] === 'فردا تمام می‌شود')
        );

        $this->artisan('sazmat:subscription-reminders')->assertSuccessful();
    }

    /*
    | مهم‌ترین حالت: کاربر از قبل تمدید کرده. رکورد قدیمی نباید باعث
    | پیامک «اشتراکت دارد تمام می‌شود» شود، چون واقعیت این نیست.
    */
    public function test_no_reminder_when_user_already_renewed(): void
    {
        $user = $this->user();

        $old = $this->subscription($user, now()->addDays(3));

        // تمدید: از جایی که قبلی تمام می‌شود ادامه پیدا می‌کند
        ServiceSubscription::create([
            'type' => 'service',
            'user_id' => $user->id,
            'service_plan_id' => $this->plan->id,
            'amount' => 150000,
            'starts_at' => now()->addDays(3),
            'ends_at' => now()->addDays(33),
            'status' => 'active',
        ]);

        $sms = $this->fakeSms();
        $sms->shouldReceive('sendRenewalReminder')->never();

        $this->artisan('sazmat:subscription-reminders')->assertSuccessful();

        $this->assertNotNull($old->fresh()->reminder_7d_sent_at);
    }

    public function test_failed_sms_is_retried_next_run(): void
    {
        $subscription = $this->subscription($this->user(), now()->addDays(5));

        $sms = $this->fakeSms();
        $sms->shouldReceive('sendRenewalReminder')
            ->once()
            ->andThrow(new \RuntimeException('سامانه در دسترس نیست'));

        $this->artisan('sazmat:subscription-reminders')->assertSuccessful();

        // چون ارسال نشده، ستون خالی می‌ماند تا فردا دوباره تلاش شود
        $this->assertNull($subscription->fresh()->reminder_7d_sent_at);
    }

    public function test_user_without_mobile_is_skipped_without_error(): void
    {
        $subscription = $this->subscription($this->emailUser(), now()->addDays(5));

        $sms = $this->fakeSms();
        $sms->shouldReceive('sendRenewalReminder')->never();

        $this->artisan('sazmat:subscription-reminders')->assertSuccessful();

        $this->assertNotNull($subscription->fresh()->reminder_7d_sent_at);
    }

    /*
    |--------------------------------------------------------------------------
    | ارائه‌دهنده‌ای که موبایل ندارد
    |--------------------------------------------------------------------------
    |
    | ⚠️ ثبت‌نام با ایمیل اصلاً شماره نمی‌گیرد، و پروفایل هم فقط نام
    | کاربری را عوض می‌کند - یعنی چنین کاربری هیچ راهی برای افزودن
    | شماره ندارد، ولی می‌تواند اشتراک بخرد.
    |
    | تا امروز یادآوری‌اش بی‌صدا رد می‌شد: اشتراک تمام می‌شد،
    | آگهی‌هایش تعلیق می‌شد، و هیچ‌کس نمی‌فهمید چرا.
    |
    | حالا سراغ شماره‌ی خودِ آگهی می‌رویم - همان که مشتری‌ها هم
    | می‌گیرند.
    */
    public function test_the_ad_phone_is_used_when_the_user_has_no_mobile(): void
    {
        $user = $this->emailUser();
        $this->subscription($user, now()->addDays(5));
        $this->ad($user, '09127776655');

        $sms = $this->fakeSms();
        $sms->shouldReceive('sendRenewalReminder')->once()->with(
            '09127776655',
            Mockery::any(),
            Mockery::any()
        );

        $this->artisan('sazmat:subscription-reminders')->assertSuccessful();
    }

    /* شماره‌ی خودِ حساب مقدم است؛ آگهی فقط جایگزین است. */
    public function test_the_account_mobile_wins_over_the_ad_phone(): void
    {
        $user = $this->user('09121112233');
        $this->subscription($user, now()->addDays(5));
        $this->ad($user, '09127776655');

        $sms = $this->fakeSms();
        $sms->shouldReceive('sendRenewalReminder')->once()->with(
            '09121112233',
            Mockery::any(),
            Mockery::any()
        );

        $this->artisan('sazmat:subscription-reminders')->assertSuccessful();
    }

    /*
    | تلفن ثابت پیامک نمی‌گیرد، پس به حساب نمی‌آید - وگرنه سامانه
    | «ارسال شد» می‌گوید و ستون پر می‌شود، در حالی که هیچ‌کس چیزی
    | دریافت نکرده.
    */
    public function test_a_landline_on_the_ad_is_not_treated_as_reachable(): void
    {
        $user = $this->emailUser();
        $this->subscription($user, now()->addDays(5));
        $this->ad($user, '02133445566');

        $sms = $this->fakeSms();
        $sms->shouldReceive('sendRenewalReminder')->never();

        $this->artisan('sazmat:subscription-reminders')->assertSuccessful();
    }

    /* و آگهیِ نوع دیگر، شماره‌ی این اشتراک نیست. */
    public function test_an_ad_of_the_other_type_is_not_borrowed(): void
    {
        $user = $this->emailUser();
        $this->subscription($user, now()->addDays(5));
        $this->ad($user, '09127776655', 'product');

        $sms = $this->fakeSms();
        $sms->shouldReceive('sendRenewalReminder')->never();

        $this->artisan('sazmat:subscription-reminders')->assertSuccessful();
    }

    public function test_dry_run_sends_nothing(): void
    {
        $subscription = $this->subscription($this->user(), now()->addDays(5));

        $sms = $this->fakeSms();
        $sms->shouldReceive('sendRenewalReminder')->never();

        $this->artisan('sazmat:subscription-reminders', ['--dry-run' => true])
            ->assertSuccessful();

        $this->assertNull($subscription->fresh()->reminder_7d_sent_at);
    }

    /*
    |--------------------------------------------------------------------------
    | بودجه‌ی یک صفحه
    |--------------------------------------------------------------------------
    |
    | ⚠️ پیامک فارسی با UCS-2 فرستاده می‌شود: تا ۷۰ کاراکتر یک صفحه،
    | از ۷۱ به بعد دو صفحه - یعنی دو برابر هزینه، روی *هر* یادآوری،
    | برای همیشه.
    |
    | اضافه‌کردن یک کلمه به متن، کاری است که هیچ‌کس هنگام انجامش
    | متوجه هزینه‌اش نمی‌شود. این تست همان لحظه جلویش را می‌گیرد.
    |
    | ⚠️ «لغو11» هم شمرده می‌شود.
    |
    | آموت آن را خودش ته هر پیامک می‌چسباند. نسخه‌ی اول این تست
    | حسابش نکرده بود و متنی را سبز کرد که در پنل ۷۱ کاراکتر شد -
    | یعنی دقیقاً یک کاراکتر بیرون از یک صفحه. چیزی که اپراتور
    | اضافه می‌کند هم بخشی از بودجه است.
    */
    public function test_the_message_still_fits_one_sms_page(): void
    {
        /*
        | متن ثابتِ پترن (بدون جای متغیرها) به‌علاوه‌ی چیزی که آموت
        | خودش می‌چسباند.
        */
        $fixed = mb_strlen("اشتراک  شما .
sazmat.com") + mb_strlen("
لغو11");

        $sent = [];

        $sms = $this->fakeSms();
        $sms->shouldReceive('sendRenewalReminder')
            ->andReturnUsing(function ($mobile, $message, $values) use (&$sent) {
                $sent[] = $values;
            });

        /*
        | هر سه مرحله، و هر دو نوع اشتراک.
        |
        | ⚠️ «محصولات» دو حرف از «خدمات» بلندتر است - و نسخه‌ی اول
        | این تست فقط خدمات می‌ساخت. همان دو حرف بود که ۶۹ را
        | می‌کرد ۷۱: تست سبز می‌ماند و پیامک واقعی دو صفحه می‌شد.
        */
        $this->subscription($this->user('09120000001'), now()->addDays(5), 'active', 'product');
        $this->subscription($this->user('09120000002'), now()->addHours(12), 'active', 'product');
        $this->subscription($this->user('09120000003'), now()->subHour(), 'expired', 'product');
        $this->subscription($this->user('09120000004'), now()->addDays(5));
        $this->subscription($this->user('09120000005'), now()->addHours(12));
        $this->subscription($this->user('09120000006'), now()->subHour(), 'expired');

        $this->artisan('sazmat:subscription-reminders')->assertSuccessful();

        $this->assertCount(6, $sent, 'هر سه مرحله در هر دو نوع باید پیامک بدهند.');

        $this->assertContains('محصولات', array_column($sent, 0), 'نوعِ بلندتر باید آزموده شود.');

        foreach ($sent as $values) {

            $length = $fixed + array_sum(array_map('mb_strlen', $values));

            $this->assertLessThanOrEqual(
                70,
                $length,
                'پیامک از یک صفحه بیرون زد (' . $length . ' کاراکتر): «'
                . implode('» و «', $values) . '». هزینه دو برابر می‌شود.'
            );
        }
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
