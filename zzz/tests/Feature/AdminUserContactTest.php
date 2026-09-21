<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| شماره‌ی کاربر در پنل مدیر
|--------------------------------------------------------------------------
|
| ⚠️ اینجا `$u->email ?: $u->mobile` نوشته شده بود - یعنی تا ایمیل
| وجود داشت، شماره اصلاً دیده نمی‌شد.
|
| تا دیروز فرقی نمی‌کرد، چون هر کاربر فقط یکی از این دو را داشت.
| حالا که کاربرِ ایمیلی برای ثبت آگهی شماره‌اش را تأیید می‌کند، هر
| دو را دارد - و آن `?:` دقیقاً همان چیزی را پنهان می‌کرد که برای
| تماس گرفتن لازم است.
|
*/
class AdminUserContactTest extends TestCase
{
    use RefreshDatabase;

    /*
    | is_admin عمداً در $fillable نیست، پس با create ست نمی‌شود -
    | forceFill لازم است. (خودِ همین تست اولین بار به این خورد.)
    */
    private function admin(): User
    {
        $user = User::create([
            'name' => 'مدیر',
            'username' => 'boss',
            'email' => 'boss@example.com',
            'password' => 'secret-password',
        ]);

        $user->forceFill(['is_admin' => true])->save();

        return $user;
    }

    /* کاربری که با ایمیل ثبت‌نام کرده و بعد شماره‌اش را تأیید کرده. */
    private function bothUser(): User
    {
        return User::create([
            'name' => 'هر دو',
            'username' => 'both',
            'email' => 'both@example.com',
            'mobile' => '09127776655',
            'password' => 'secret-password',
        ]);
    }

    public function test_the_list_shows_the_mobile_even_when_an_email_exists(): void
    {
        $this->bothUser();

        $this->actingAs($this->admin())
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('09127776655', false)
            ->assertSee('both@example.com', false);
    }

    public function test_the_list_still_shows_a_mobile_only_user(): void
    {
        User::create([
            'name' => 'موبایلی',
            'username' => 'mob',
            'mobile' => '09121112233',
            'password' => 'secret-password',
        ]);

        $this->actingAs($this->admin())
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('09121112233', false);
    }

    /*
    | و صفحه‌ی خودِ کاربر، که فقط ایمیل را نشان می‌داد: کاربرِ موبایلی
    | آنجا یک خط خالی می‌دید.
    */
    public function test_the_detail_page_shows_both(): void
    {
        $user = $this->bothUser();

        $this->actingAs($this->admin())
            ->get(route('admin.users.show', $user))
            ->assertOk()
            ->assertSee('09127776655', false)
            ->assertSee('both@example.com', false);
    }

    public function test_the_detail_page_shows_a_mobile_only_user(): void
    {
        $user = User::create([
            'name' => 'موبایلی',
            'username' => 'mob2',
            'mobile' => '09129998877',
            'password' => 'secret-password',
        ]);

        $this->actingAs($this->admin())
            ->get(route('admin.users.show', $user))
            ->assertOk()
            ->assertSee('09129998877', false);
    }

    /* و کسی که هیچ‌کدام را ندارد نباید صفحه را بشکند. */
    public function test_a_user_with_neither_is_shown_as_a_dash(): void
    {
        $user = User::create([
            'name' => 'بدون راه تماس',
            'username' => 'nothing',
            'password' => 'secret-password',
        ]);

        $this->actingAs($this->admin())
            ->get(route('admin.users.show', $user))
            ->assertOk()
            ->assertSee('—', false);
    }
}
