<?php

namespace Tests\Feature;

use App\Models\Ad;
use App\Models\Category;
use App\Models\City;
use App\Models\Province;
use App\Models\ServicePlan;
use App\Models\ServiceSubscription;
use App\Models\User;
use Illuminate\Console\Scheduling\CallbackEvent;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| کارهای ساعتی هم باید ردِ پا بگذارند
|--------------------------------------------------------------------------
|
| ⚠️ این از بازبینیِ پروژه‌ی روی هاست آمده.
|
| دو کار ساعتی در bootstrap/app.php هیچ خطی در cron-tasks.log
| نمی‌نوشتند:
|
|   - تعلیق آگهی‌ها وقتی اشتراک تمام می‌شود
|   - پاک کردنِ همیشگیِ آگهی‌هایی که ۶ ماه تعلیق مانده‌اند
|
| صاحب سایت به دیتابیس دسترسی ندارد. روزی که اولین اشتراکِ واقعی تمام
| شود، تنها راهِ فهمیدنِ اینکه آگهی‌ها واقعاً تعلیق شدند همین خط است.
| و دومی آگهی پاک می‌کند - بی‌صدا.
|
| ⚠️ فقط وقتی کاری انجام شده می‌نویسند. کار ساعتی است؛ یک خطِ خالی در
| هر ساعت لاگ را بی‌مصرف می‌کرد. اینکه اجرا شده را cron.log نشان
| می‌دهد، که حالا با نامِ کار.
|
*/
class HourlyJobsLeaveATraceTest extends TestCase
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

    private function spyCronChannel(): \Mockery\MockInterface
    {
        $channel = \Mockery::spy(\Psr\Log\LoggerInterface::class);

        Log::shouldReceive('channel')->with('cron')->andReturn($channel);

        return $channel;
    }

    /* همان کاری که زمان‌بند روی هاست اجرا می‌کند، با نامش. */
    private function runJob(string $name): void
    {
        $this->artisan('schedule:list')->run();

        $event = collect(app(Schedule::class)->events())
            ->first(fn ($e) => $e instanceof CallbackEvent && $e->description === $name);

        $this->assertNotNull($event, "کارِ «{$name}» در زمان‌بند نیست.");

        $event->run($this->app);
    }

    private function provider(): User
    {
        return User::create([
            'name' => 'ارائه‌دهنده', 'username' => 'p' . User::count(),
            'mobile' => '0912000' . str_pad((string) User::count(), 4, '0', STR_PAD_LEFT),
            'password' => 'secret-password',
        ]);
    }

    private function ad(User $user, array $extra = []): Ad
    {
        $province = Province::firstOrCreate(['slug' => 'tehran'], ['name' => 'تهران']);
        $city = City::firstOrCreate(['province_id' => $province->id, 'slug' => 'tehran'], ['name' => 'تهران']);
        $category = Category::firstOrCreate(
            ['slug' => 'build'],
            ['name' => 'ساخت', 'type' => 'service', 'is_active' => true]
        );

        return Ad::create(array_merge([
            'user_id' => $user->id, 'category_id' => $category->id,
            'province_id' => $province->id, 'city_id' => $city->id,
            'type' => 'service', 'title' => 'خدمت ' . Ad::count(),
            'description' => 'توضیح کافی برای یک آگهی خدمات ساختمانی.',
            'price' => 100000, 'address' => 'آدرس', 'phone' => '09121234567',
            'status' => 'approved',
        ], $extra));
    }

    private function subscription(User $user, array $extra = []): ServiceSubscription
    {
        return ServiceSubscription::create(array_merge([
            'type' => 'service', 'user_id' => $user->id,
            'service_plan_id' => $this->plan->id, 'amount' => 150000,
            'starts_at' => now()->subMonth(), 'ends_at' => now()->subHour(),
            'status' => 'active',
        ], $extra));
    }

    /*
    |--------------------------------------------------------------------------
    | تعلیق
    |--------------------------------------------------------------------------
    */
    public function test_expiring_a_subscription_says_how_many_ads_were_suspended(): void
    {
        $user = $this->provider();
        $sub = $this->subscription($user);
        $this->ad($user);
        $this->ad($user);

        $channel = $this->spyCronChannel();

        $this->runJob('sazmat:expire-subscriptions');

        $channel->shouldHaveReceived('info')->once()->withArgs(
            fn ($message, $context = []) => $message === 'sazmat:expire-subscriptions'
                && $context['اشتراک تمام‌شده'] === 1
                && $context['آگهی تعلیق‌شده'] === 2
                && $context['شناسه‌ها'] === [$sub->id]
        );

        /* و کار خودش را هم کرده باشد - لاگ دروغ نگوید. */
        $this->assertSame('expired', $sub->fresh()->status);
        $this->assertSame(2, Ad::where('is_suspended', true)->count());
    }

    /* ساعت‌هایی که کاری نبوده، سکوت. */
    public function test_a_quiet_hour_writes_nothing(): void
    {
        $channel = $this->spyCronChannel();

        $this->runJob('sazmat:expire-subscriptions');
        $this->runJob('sazmat:purge-suspended');

        $channel->shouldNotHaveReceived('info');
    }

    /*
    |--------------------------------------------------------------------------
    | پاک کردنِ همیشگی
    |--------------------------------------------------------------------------
    */
    public function test_purging_says_which_ads_were_deleted(): void
    {
        $user = $this->provider();

        $old = $this->ad($user, ['is_suspended' => true, 'suspended_at' => now()->subMonths(7)]);
        $recent = $this->ad($user, ['is_suspended' => true, 'suspended_at' => now()->subMonth()]);

        $this->subscription($user, ['status' => 'expired', 'grace_until' => now()->subDay()]);

        $channel = $this->spyCronChannel();

        $this->runJob('sazmat:purge-suspended');

        $channel->shouldHaveReceived('info')->once()->withArgs(
            fn ($message, $context = []) => $message === 'sazmat:purge-suspended'
                && $context['آگهی پاک‌شده'] === 1
                && $context['شناسه‌ی آگهی‌ها'] === [$old->id]
                && $context['اشتراک پاک‌شده'] === 1
        );

        $this->assertNull(Ad::find($old->id));
        $this->assertNotNull(Ad::find($recent->id));
    }

    /*
    | و نام‌ها در cron.log بیایند، نه «[Callback]» بی‌نام - تا از
    | همان فایل هم معلوم باشد کدام کار اجرا شده.
    */
    public function test_every_scheduled_task_has_a_name(): void
    {
        $this->artisan('schedule:list')->run();

        foreach (app(Schedule::class)->events() as $event) {
            $this->assertNotEmpty(
                $event->description,
                'یک کار زمان‌بند نام ندارد؛ در cron.log فقط «[Callback]» دیده می‌شود.'
            );
        }
    }
}
