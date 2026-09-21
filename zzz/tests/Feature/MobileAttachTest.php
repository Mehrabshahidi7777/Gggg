<?php

namespace Tests\Feature;

use App\Models\LoginOtp;
use App\Models\User;
use App\Services\AmootSmsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Mockery;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| ثبت آگهی، بدون شماره‌ی تأییدشده نه
|--------------------------------------------------------------------------
|
| ثبت‌نام دو راه دارد: ایمیل یا موبایل. راهِ ایمیل هیچ شماره‌ای
| نمی‌گیرد و پروفایل هم فقط نام کاربری را عوض می‌کند - یعنی چنین
| کاربری هیچ راهی برای ثبت شماره نداشت، ولی می‌توانست آگهی بگذارد
| و اشتراک بخرد.
|
| نتیجه‌اش این بود که یادآوری تمدید بی‌صدا رد می‌شد: اشتراک تمام
| می‌شد، آگهی‌ها تعلیق می‌شدند، و نه او می‌فهمید چرا نه مدیر سایت.
|
*/
class MobileAttachTest extends TestCase
{
    use RefreshDatabase;

    private function emailUser(): User
    {
        return User::create([
            'name' => 'کاربر ایمیلی',
            'username' => 'emailuser',
            'email' => 'a@example.com',
            'password' => 'secret-password',
        ]);
    }

    private function mobileUser(string $mobile = '09121234567'): User
    {
        return User::create([
            'name' => 'کاربر موبایلی',
            'username' => 'mobileuser',
            'mobile' => $mobile,
            'password' => 'secret-password',
        ]);
    }

    /* پیامک را جعل می‌کنیم تا تست به سامانه‌ی واقعی وصل نشود. */
    private function fakeSms(): Mockery\MockInterface
    {
        $mock = Mockery::mock(AmootSmsService::class);
        $this->app->instance(AmootSmsService::class, $mock);

        return $mock;
    }

    /*
    |--------------------------------------------------------------------------
    | دروازه
    |--------------------------------------------------------------------------
    */
    public function test_a_user_without_a_mobile_cannot_reach_the_ad_form(): void
    {
        $this->actingAs($this->emailUser())
            ->get(route('ad.create'))
            ->assertRedirect(route('mobile.attach'));
    }

    /* و نه با ارسال مستقیم فرم، که راهِ دورزدنِ صفحه است. */
    public function test_the_post_is_blocked_too(): void
    {
        $this->actingAs($this->emailUser())
            ->post(route('ad.store'), [])
            ->assertRedirect(route('mobile.attach'));
    }

    /*
    | ⚠️ کسی که با موبایل ثبت‌نام کرده نباید حتی متوجه این مرحله شود.
    */
    public function test_a_user_with_a_mobile_passes_straight_through(): void
    {
        $this->actingAs($this->mobileUser())
            ->get(route('ad.create'))
            ->assertOk();
    }

    /*
    | و اگر از قبل شماره دارد، این صفحه کاری برایش ندارد.
    */
    public function test_the_page_sends_a_verified_user_away(): void
    {
        $this->actingAs($this->mobileUser())
            ->get(route('mobile.attach'))
            ->assertRedirect();
    }

    /*
    | ⚠️ مقصد باید نگه داشته شود.
    |
    | کسی که وسط ثبت آگهی متوقف شده، اگر بعد از تأیید به صفحه‌ی خانه
    | پرت شود همان‌جا رهایش می‌کند.
    */
    public function test_the_user_lands_back_on_the_ad_form_afterwards(): void
    {
        $user = $this->emailUser();

        $this->actingAs($user)->get(route('ad.create'));

        $this->assertSame(route('ad.create'), session('url.intended'));
    }

    /*
    |--------------------------------------------------------------------------
    | خودِ تأیید
    |--------------------------------------------------------------------------
    */
    public function test_the_whole_flow_attaches_the_number(): void
    {
        $user = $this->emailUser();

        $sms = $this->fakeSms();

        /* کد را از خودِ سرویس می‌قاپیم تا مثل کاربر واردش کنیم. */
        $code = null;
        $sms->shouldReceive('sendOtp')
            ->once()
            ->andReturnUsing(function ($mobile, $sent) use (&$code) {
                $code = $sent;
            });

        $this->actingAs($user)
            ->post(route('mobile.attach.request'), ['mobile' => '09127776655'])
            ->assertRedirect(route('mobile.attach'));

        $this->assertNotNull($code, 'کد باید فرستاده شده باشد.');

        $this->actingAs($user)
            ->post(route('mobile.attach.verify'), ['code' => $code])
            ->assertRedirect();

        $this->assertSame('09127776655', $user->fresh()->mobile);
    }

    /* و بعد از آن، فرم آگهی باز می‌شود. */
    public function test_the_ad_form_opens_once_the_number_is_verified(): void
    {
        $user = $this->emailUser();

        $user->forceFill(['mobile' => '09127776655'])->save();

        $this->actingAs($user)->get(route('ad.create'))->assertOk();
    }

    /*
    | ⚠️ شماره‌ی تأییدنشده بدتر از نداشتنِ شماره است: سیستم فکر
    | می‌کند راهی برای رسیدن به کاربر دارد، در حالی که ندارد.
    */
    public function test_a_wrong_code_does_not_attach_anything(): void
    {
        $user = $this->emailUser();

        $sms = $this->fakeSms();
        $sms->shouldReceive('sendOtp')->once();

        $this->actingAs($user)->post(route('mobile.attach.request'), [
            'mobile' => '09127776655',
        ]);

        $this->actingAs($user)
            ->post(route('mobile.attach.verify'), ['code' => '000000'])
            ->assertSessionHasErrors('code');

        $this->assertNull($user->fresh()->mobile);
    }

    public function test_an_expired_code_does_not_attach_anything(): void
    {
        $user = $this->emailUser();

        $sms = $this->fakeSms();
        $code = null;
        $sms->shouldReceive('sendOtp')->andReturnUsing(function ($m, $c) use (&$code) {
            $code = $c;
        });

        $this->actingAs($user)->post(route('mobile.attach.request'), [
            'mobile' => '09127776655',
        ]);

        $this->travel(4)->minutes();

        $this->actingAs($user)
            ->post(route('mobile.attach.verify'), ['code' => $code])
            ->assertSessionHasErrors('code');

        $this->assertNull($user->fresh()->mobile);
    }

    /*
    |--------------------------------------------------------------------------
    | شماره‌ی تکراری
    |--------------------------------------------------------------------------
    |
    | ستون mobile یکتاست. بدون بررسی، ذخیره‌ی نهایی با خطای دیتابیس
    | می‌افتاد و کاربر بعد از واردکردن کد یک صفحه‌ی ۵۰۰ می‌دید.
    */
    public function test_a_number_that_belongs_to_someone_else_is_refused(): void
    {
        $this->mobileUser('09127776655');

        $sms = $this->fakeSms();
        $sms->shouldReceive('sendOtp')->never();

        $this->actingAs($this->emailUser())
            ->post(route('mobile.attach.request'), ['mobile' => '09127776655'])
            ->assertSessionHasErrors('mobile');
    }

    /*
    | و شماره در هر شکلی که نوشته شود یکدست می‌شود - وگرنه همان آدم
    | با ۹۸+ می‌توانست یک حساب دوم بسازد.
    */
    public function test_the_number_is_normalised_before_the_duplicate_check(): void
    {
        $this->mobileUser('09127776655');

        $sms = $this->fakeSms();
        $sms->shouldReceive('sendOtp')->never();

        $this->actingAs($this->emailUser())
            ->post(route('mobile.attach.request'), ['mobile' => '+989127776655'])
            ->assertSessionHasErrors('mobile');
    }

    public function test_a_landline_is_not_accepted(): void
    {
        $sms = $this->fakeSms();
        $sms->shouldReceive('sendOtp')->never();

        $this->actingAs($this->emailUser())
            ->post(route('mobile.attach.request'), ['mobile' => '02133445566'])
            ->assertSessionHasErrors('mobile');
    }

    /*
    | اگر پیامک نرفت، کدی هم نباید بماند: کاربر چیزی برای واردکردن
    | ندارد و ردیف فقط جدول را شلوغ می‌کند.
    */
    public function test_a_failed_sms_leaves_no_orphan_code(): void
    {
        $sms = $this->fakeSms();
        $sms->shouldReceive('sendOtp')
            ->once()
            ->andThrow(new \RuntimeException('سامانه در دسترس نیست'));

        $this->actingAs($this->emailUser())
            ->post(route('mobile.attach.request'), ['mobile' => '09127776655'])
            ->assertSessionHasErrors('mobile');

        $this->assertSame(0, LoginOtp::where('purpose', 'attach')->count());
    }

    /*
    | کدِ ورود نباید برای افزودن شماره کار کند و برعکس - وگرنه یک
    | کدِ لو رفته در یک مسیر، مسیر دیگر را هم باز می‌کند.
    */
    public function test_a_login_code_cannot_be_used_to_attach_a_number(): void
    {
        $user = $this->emailUser();

        LoginOtp::create([
            'mobile' => '09127776655',
            'code_hash' => Hash::make('123456'),
            'purpose' => 'login',
            'expires_at' => now()->addMinutes(3),
        ]);

        $sms = $this->fakeSms();
        $sms->shouldReceive('sendOtp')->once();

        $this->actingAs($user)->post(route('mobile.attach.request'), [
            'mobile' => '09127776655',
        ]);

        $this->actingAs($user)
            ->post(route('mobile.attach.verify'), ['code' => '123456'])
            ->assertSessionHasErrors('code');

        $this->assertNull($user->fresh()->mobile);
    }

    /*
    |--------------------------------------------------------------------------
    | خودِ صفحه
    |--------------------------------------------------------------------------
    |
    | اینجا کاربر را وسط کارش متوقف می‌کنیم، پس اولین چیزی که
    | می‌بیند باید دلیل باشد نه یک فیلد خالی. کسی که نفهمد چرا
    | شماره‌اش را می‌خواهیم، یا می‌رود یا شماره‌ی الکی می‌زند.
    */
    public function test_the_page_says_why(): void
    {
        $this->actingAs($this->emailUser())
            ->get(route('mobile.attach'))
            ->assertOk()
            ->assertSee('اشتراک بی‌صدا تمام می‌شود', false)
            ->assertSee('در آگهی نمایش داده نمی‌شود', false);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
