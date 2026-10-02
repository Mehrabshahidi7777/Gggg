<?php

namespace Tests\Feature;

use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| دایره‌ی شناور نباید روی «© سازمت» بیفتد
|--------------------------------------------------------------------------
|
| ⚠️ این تست از یک باگ واقعی آمده که مهراب در گوشی دید: دکمه‌ی
| گفت‌وگو درست روی خطِ آخر فوتر نشسته بود.
|
| علتش این است که دکمه position: fixed است - یعنی از جریان صفحه
| بیرون است و هیچ‌چیز برایش جا باز نمی‌کند. تا وقتی پایین صفحه
| محتوایی نیست کسی متوجه نمی‌شود، ولی انتهای فوتر دقیقاً همان‌جاست.
|
| در کرومیوم روی ۱۴ عرض از ۳۶۰ تا ۱۴۴۰ اندازه گرفته شد: با
| padding قبلی (۲۸ پیکسل) روی ۱۳ تا از ۱۴ تا روی هم می‌افتادند -
| نه فقط گوشی، لپ‌تاپِ ۱۲۸۰ هم. تنها ۱۴۴۰ سالم بود، چون آنجا ظرف
| آن‌قدر باریک می‌شود که دایره بیرونش می‌ماند.
|
| راه‌حل: فوتر به اندازه‌ی باندِ دایره پایین جا باز می‌کند.
|
| ⚠️ و این تست عددها را از خودِ CSS درمی‌آورد و با هم می‌سنجد، نه
| اینکه دنبال رشته‌ی «۸۸» بگردد. پس اگر روزی اندازه‌ی دکمه یا
| فاصله‌اش از لبه عوض شود و کسی یادش برود فوتر را هم تنظیم کند،
| همین‌جا قرمز می‌شود - که دقیقاً همان اشتباهی است که این بار
| اتفاق افتاد.
|
*/
class FooterClearsTheChatBubbleTest extends TestCase
{
    /* فاصله‌ی نفس‌کشیدن بین دایره و متن. */
    private const BREATHING_ROOM = 8;

    private function css(): string
    {
        return file_get_contents(base_path('../public_html/css/sazmat-theme.css'));
    }

    /* بلوک @media گوشی، جدا از بقیه‌ی فایل. */
    private function mobileBlock(): string
    {
        preg_match('/@media \(max-width: 480px\) \{(.*?)\n\}/s', $this->css(), $m);

        $this->assertNotEmpty($m, 'بلوک @media (max-width: 480px) پیدا نشد.');

        return $m[1];
    }

    private function px(string $haystack, string $pattern, string $what): int
    {
        preg_match($pattern, $haystack, $m);

        $this->assertNotEmpty($m, "در CSS پیدا نشد: {$what}");

        return (int) $m[1];
    }

    public function test_the_footer_reserves_room_for_the_bubble_on_desktop(): void
    {
        $css = $this->css();

        $bottom = $this->px($css, '/\.chat-bubble \{[^}]*bottom:\s*(\d+)px/s', 'فاصله‌ی دایره از پایین');
        $size = $this->px($css, '/\.chat-bubble__fab \{[^}]*height:\s*(\d+)px/s', 'ارتفاع دایره');
        $pad = $this->px($css, '/\.site-footer \{[^}]*padding:\s*\d+px\s+\d+\s+(\d+)px/s', 'padding پایین فوتر');

        $band = $bottom + $size;

        $this->assertGreaterThanOrEqual(
            $band + self::BREATHING_ROOM,
            $pad,
            sprintf(
                'دایره تا %d پیکسلیِ پایین را می‌گیرد ولی فوتر فقط %d جا باز کرده - روی هم می‌افتند.',
                $band,
                $pad
            )
        );
    }

    /*
    | و روی گوشی، که هم دایره کوچک‌تر است و هم فاصله‌اش از لبه کمتر.
    |
    | هر سه عدد در همان بلوک @media هستند، پس اگر یکی عوض شود و
    | آن یکی نه، همین‌جا معلوم می‌شود.
    */
    public function test_the_footer_reserves_room_for_the_bubble_on_a_phone(): void
    {
        $block = $this->mobileBlock();

        $bottom = $this->px($block, '/\.chat-bubble \{[^}]*bottom:\s*(\d+)px/s', 'فاصله‌ی دایره از پایین (گوشی)');
        $size = $this->px($block, '/\.chat-bubble__fab \{[^}]*height:\s*(\d+)px/s', 'ارتفاع دایره (گوشی)');
        $pad = $this->px($block, '/\.site-footer \{[^}]*padding-bottom:\s*(\d+)px/s', 'padding پایین فوتر (گوشی)');

        $band = $bottom + $size;

        $this->assertGreaterThanOrEqual(
            $band + self::BREATHING_ROOM,
            $pad,
            sprintf(
                'روی گوشی دایره تا %d پیکسلیِ پایین را می‌گیرد ولی فوتر فقط %d جا باز کرده.',
                $band,
                $pad
            )
        );
    }

    /*
    | و خودِ خطِ آخر هنوز سر جایش باشد - وگرنه «جا باز شد» را
    | می‌شد با حذف کردنش هم به دست آورد.
    */
    public function test_the_copyright_line_is_still_there(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('footer-bottom', $html);
        $this->assertStringContainsString('سازمت', $html);
        $this->assertStringContainsString('©', $html);
    }
}
