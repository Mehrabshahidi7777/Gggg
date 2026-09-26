<?php

namespace Tests\Feature;

use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| فیلدهای «حداقل / حداکثر قیمت»
|--------------------------------------------------------------------------
|
| ⚠️ این تست از یک باگ واقعی آمده: روی دسکتاپ، متنِ «حداکثر قیمت»
| داخل فیلد بریده می‌شد.
|
| علتش عرض نبود، فونت بود. قاعده‌ی مشترکِ نوار فیلتر این فیلدها را
| شامل نمی‌شد:
|
|     .filter-bar input[type="text"], .filter-bar select { ... }
|
| پس select و جستجو فونت ۱۵.۲ پیکسلی می‌گرفتند و فیلد قیمت ۱۷ -
| متنِ بزرگ‌تر در جعبه‌ی کوچک‌تر (max-width: 120px). در کرومیوم
| اندازه گرفته شد: ۱۰۳ پیکسل متن در ۹۴ پیکسل جا.
|
| حالا هر دو در یک قاعده‌اند و عرض هم با ch حساب می‌شود، یعنی با
| فونت جابه‌جا می‌شود نه با یک عددِ ثابتِ حدسی.
|
*/
class PriceFilterFieldsTest extends TestCase
{
    private function css(): string
    {
        return file_get_contents(base_path('../public_html/css/sazmat-theme.css'));
    }

    /*
    | فیلد عددی باید در همان قاعده‌ای باشد که فونت و padding بقیه‌ی
    | نوار را تعیین می‌کند.
    */
    public function test_the_price_fields_share_the_other_fields_styling(): void
    {
        $this->assertMatchesRegularExpression(
            '/\.filter-bar input\[type="number"\],\s*\n?\s*\.filter-bar select[^{]*\{[^}]*font-size/s',
            $this->css(),
            'فیلد قیمت باید با بقیه‌ی فیلدهای نوار یک فونت داشته باشد.'
        );
    }

    /*
    | ⚠️ و عرضش نباید عددِ ثابت باشد.
    |
    | max-width: 120px عددی بود که با چشم انتخاب شده بود و با فونت
    | وزیرمتن کم می‌آورد. ch پهنای رقم در همان فونت است، پس اگر روزی
    | فونت یا اندازه عوض شود این هم با آن جابه‌جا می‌شود.
    */
    public function test_the_width_follows_the_font_not_a_guessed_number(): void
    {
        $this->assertMatchesRegularExpression(
            '/\.filter-bar input\[type="number"\] \{[^}]*min-width:\s*\d+ch/s',
            $this->css(),
            'عرض فیلد قیمت باید با ch حساب شود، نه با پیکسلِ ثابت.'
        );
    }

    /*
    | و روی گوشی، همان min-width باید آزاد شود.
    |
    | نوار آنجا شبکه‌ی دوستونی می‌شود: دو ستون ۱۵۰ پیکسلی به‌اضافه‌ی
    | فاصله می‌شود ۳۰۸، در حالی که در صفحه‌ی ۳۶۰ پیکسلی فقط ۲۸۶ جا
    | هست - و خانه‌ها از کادر سفید بیرون می‌زدند.
    */
    public function test_the_mobile_grid_is_not_forced_wider_than_its_column(): void
    {
        $this->assertMatchesRegularExpression(
            '/\.filter-bar select,\s*\n?\s*\.filter-bar input\[type="number"\] \{[^}]*min-width:\s*0/s',
            $this->css(),
            'روی گوشی، عرضِ خانه‌ها را باید خودِ شبکه تعیین کند.'
        );
    }

    /* و خودِ فیلدها هنوز سر جایشان باشند. */
    public function test_both_price_fields_are_on_the_listing(): void
    {
        $listing = file_get_contents(resource_path('views/front/listing.blade.php'));

        $this->assertStringContainsString('name="min_price"', $listing);
        $this->assertStringContainsString('name="max_price"', $listing);
    }
}
