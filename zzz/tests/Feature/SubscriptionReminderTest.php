<?php

namespace Tests\Feature;

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

    private function subscription(User $user, $endsAt, string $status = 'active'): ServiceSubscription
    {
        return ServiceSubscription::create([
            'type' => 'service',
            'user_id' => $user->id,
            'service_plan_id' => $this->plan->id,
            'amount' => 150000,
            'starts_at' => now()->subMonth(),
            'ends_at' => $endsAt,
            'status' => $status,
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
            Mockery::on(fn ($v) => $v[2] === 'تمام شد و آگهی‌هایتان تعلیق شدند')
        );

        $this->artisan('sazmat:subscription-reminders')->assertSuccessful();
    }

    public function test_the_seven_day_stage_names_the_days(): void
    {
        $this->subscription($this->user(), now()->addDays(5));

        $sms = $this->fakeSms();
        $sms->shouldReceive('sendRenewalReminder')->once()->with(
            '09121234567',
            Mockery::any(),
            Mockery::on(fn ($v) => count($v) === 3
                && $v[1] === 'خدمات'
                && $v[2] === 'تا 7 روز دیگر تمام می‌شود')
        );

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
            Mockery::on(fn ($v) => $v[2] === 'فردا تمام می‌شود')
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
        $user = User::create([
            'name' => 'بدون موبایل',
            'username' => 'nomobile',
            'email' => 'x@example.com',
            'password' => 'secret-password',
        ]);

        $subscription = $this->subscription($user, now()->addDays(5));

        $sms = $this->fakeSms();
        $sms->shouldReceive('sendRenewalReminder')->never();

        $this->artisan('sazmat:subscription-reminders')->assertSuccessful();

        $this->assertNotNull($subscription->fresh()->reminder_7d_sent_at);
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

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
