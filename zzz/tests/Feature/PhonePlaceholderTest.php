<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| راهنمای فیلد شماره
|--------------------------------------------------------------------------
|
| ⚠️ «09121234567» شماره‌ی واقعیِ یک نفر است.
|
| بعضی کاربرها فکر می‌کنند باید همان را وارد کنند، و بقیه لحظه‌ای
| مکث می‌کنند تا بفهمند شماره‌ی خودشان آنجا نوشته شده یا نه. نقطه
| این ابهام را ندارد و شکلِ لازم را هم نشان می‌دهد:
|
|     0913 + هفت نقطه = ۱۱ کاراکتر
|
*/
class PhonePlaceholderTest extends TestCase
{
    use RefreshDatabase;

    private const HINT = '0913•••••••';

    public static function views(): array
    {
        return [
            'تأیید شماره' => ['front/verify-mobile'],
            'ثبت آگهی' => ['front/submit-ad'],
            'ویرایش آگهی' => ['front/ad-edit'],
            'تسویه‌حساب' => ['front/checkout'],
        ];
    }

    #[DataProvider('views')]
    public function test_the_phone_field_is_hinted_with_dots(string $view): void
    {
        $source = file_get_contents(resource_path("views/{$view}.blade.php"));

        $this->assertStringContainsString(
            'placeholder="' . self::HINT . '"',
            $source
        );

        /* و هیچ شماره‌ی نمونه‌ای نمانده باشد. */
        $this->assertDoesNotMatchRegularExpression(
            '/placeholder="[^"]*09\d{9}/',
            $source,
            'یک شماره‌ی واقعی به‌عنوان نمونه در فیلد مانده است.'
        );
    }

    /* و همان چیزی که رندر می‌شود، نه فقط آنچه در فایل است. */
    public function test_the_rendered_field_shows_the_dots(): void
    {
        $user = User::create([
            'name' => 'کاربر',
            'username' => 'emailonly',
            'email' => 'x@example.com',
            'password' => 'secret-password',
        ]);

        $this->actingAs($user)
            ->get(route('mobile.attach'))
            ->assertOk()
            ->assertSee('placeholder="' . self::HINT . '"', false);
    }

    /*
    | ⚠️ طولش هم مهم است: ۱۱، یعنی همان تعداد رقمِ یک موبایل ایران.
    | نقطه‌ی کم یا زیاد، راهنما را به دروغ تبدیل می‌کند.
    */
    public function test_the_hint_is_as_long_as_a_real_number(): void
    {
        $this->assertSame(11, mb_strlen(self::HINT));
    }
}
