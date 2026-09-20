<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

/*
|--------------------------------------------------------------------------
| چرا این فایل اضافه شد
|--------------------------------------------------------------------------
|
| bootstrap/providers.php از همان روز اول App\Providers\AppServiceProvider
| را صدا می‌زد، ولی پوشه‌ی app/Providers خالی بود و این کلاس اصلاً وجود
| نداشت.
|
| سایت با این حال بالا می‌آمد، و دلیلش یک اتفاقِ کاملاً تصادفی بود:
| کامپوزر نام‌فضای \App را به دو پوشه وصل می‌کند -
|
|     app/
|     vendor/laravel/pint/app/
|
| و چون فایل ما نبود، PHP سراغ دومی می‌رفت و در عمل
| AppServiceProviderِ «Laravel Pint» (ابزار فرمت‌کردن کد) به‌عنوان
| سرویس‌پروایدرِ سایت ثبت می‌شد. یعنی روی هر درخواست، سرویس‌های
| PhpCsFixer و Prettier در کانتینر ثبت می‌شدند - بی‌فایده، و بدتر از
| آن: اگر روزی وابستگی‌های dev حذف شوند (composer install --no-dev)،
| پوشه‌ی pint هم می‌رود و کل سایت با خطای
| «Class App\Providers\AppServiceProvider not found» می‌افتد.
|
| با وجود این فایل، چون app/ در فهرست کامپوزر جلوتر از pint است،
| همین کلاس برداشته می‌شود و آن مسیر تصادفی برای همیشه بسته است.
|
*/
class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->forceHttpsUrls();
    }

    /*
    |--------------------------------------------------------------------------
    | اجبارِ https در آدرس‌هایی که خود سایت می‌سازد
    |--------------------------------------------------------------------------
    |
    | مسئله‌ی واقعی اینجاست: هر آدرسی که با route() یا url() ساخته
    | می‌شود پروتکلش را از درخواستِ جاری برمی‌دارد. اگر سایت پشت یک
    | CDN یا لودبالانسر باشد (که تقریباً همیشه هست)، ارتباطِ بین CDN و
    | سرورِ خودمان ممکن است http باشد؛ آن‌وقت لاراول فکر می‌کند
    | درخواست http بوده و می‌سازد:
    |
    |     http://sazmat.com/ad/...
    |
    | این آدرس‌ها دقیقاً همان‌هایی هستند که داخل sitemap.xml و تگ
    | canonical به گوگل تحویل داده می‌شوند. نتیجه:
    |
    |   - گوگل نسخه‌ی http و https را دو صفحه‌ی جدا حساب می‌کند،
    |   - اعتبار هر صفحه بین این دو پخش می‌شود،
    |   - و در سرچ کنسول (که روی https://sazmat.com ثبت شده) بخشی از
    |     آدرس‌های sitemap بیرون از دامنه‌ی تأییدشده دیده می‌شوند و
    |     رد می‌شوند.
    |
    | تنظیم پیش‌فرض هیچ کار دستی نمی‌خواهد: اگر APP_URL در فایل .env
    | با https شروع شود، اجبار روشن است. برای محیط توسعه‌ی محلی که
    | APP_URL روی http است، خاموش می‌ماند و چیزی خراب نمی‌شود.
    |
    | اگر لازم شد می‌توان با FORCE_HTTPS=true یا false صراحتاً دستور
    | داد و تشخیص خودکار را دور زد.
    |
    */
    private function forceHttpsUrls(): void
    {
        $forced = config('seo.force_https');

        if ($forced === null) {
            $forced = str_starts_with((string) config('app.url'), 'https://');
        }

        if ($forced) {
            URL::forceScheme('https');
        }
    }
}
