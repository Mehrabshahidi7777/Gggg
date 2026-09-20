<?php

namespace Tests\Feature;

use App\Providers\AppServiceProvider;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| سرویس‌پروایدر اصلی برنامه
|--------------------------------------------------------------------------
|
| این تست‌ها یک اشتباه واقعی را قفل می‌کنند: پوشه‌ی app/Providers خالی
| بود در حالی که bootstrap/providers.php این کلاس را صدا می‌زد، و
| کامپوزر بی‌سروصدا AppServiceProviderِ بسته‌ی laravel/pint را برمی‌داشت.
| (شرح کامل در خودِ AppServiceProvider.)
|
*/
class AppServiceProviderTest extends TestCase
{
    /*
    | اگر این تست بشکند یعنی فایل app/Providers/AppServiceProvider.php
    | دوباره گم شده و سایت دارد سرویس‌پروایدرِ یک بسته‌ی dev را اجرا
    | می‌کند - همان وضعی که با حذف وابستگی‌های dev، کل سایت را ۵۰۰
    | می‌کرد.
    */
    public function test_the_app_uses_its_own_provider_and_not_one_from_vendor(): void
    {
        $file = (new \ReflectionClass(AppServiceProvider::class))->getFileName();

        $this->assertSame(
            realpath(app_path('Providers/AppServiceProvider.php')),
            realpath($file),
            'AppServiceProvider از vendor برداشته شده، نه از app/.'
        );

        $this->assertStringNotContainsString(
            DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR,
            $file
        );
    }

    public function test_the_provider_is_actually_registered(): void
    {
        $this->assertNotNull(
            $this->app->getProvider(AppServiceProvider::class),
            'AppServiceProvider در bootstrap/providers.php ثبت نشده است.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | اجبارِ https
    |--------------------------------------------------------------------------
    |
    | نکته‌ی مهم: وقتی درخواستی در جریان باشد، لاراول ریشه‌ی آدرس‌ها را
    | از خودِ درخواست برمی‌دارد، نه از APP_URL. بنابراین این تست‌ها
    | عمداً درخواستی با پروتکل http می‌سازند - دقیقاً همان چیزی که
    | سرور وقتی پشت CDN باشد می‌بیند - و بررسی می‌کنند که آدرسِ
    | تولیدشده با این حال https باشد.
    */
    public function test_https_is_forced_when_app_url_is_https(): void
    {
        $url = $this->bootWith(['APP_URL' => 'https://sazmat.com']);

        $this->assertSame('https://sazmat.com', $url);
    }

    public function test_https_is_not_forced_for_a_plain_http_app_url(): void
    {
        $url = $this->bootWith(['APP_URL' => 'http://sazmat.com']);

        $this->assertSame('http://sazmat.com', $url);
    }

    /*
    | FORCE_HTTPS باید بتواند تشخیص خودکار را در هر دو جهت دور بزند.
    */
    public function test_force_https_true_overrides_an_http_app_url(): void
    {
        $url = $this->bootWith([
            'APP_URL' => 'http://sazmat.com',
            'FORCE_HTTPS' => 'true',
        ]);

        $this->assertSame('https://sazmat.com', $url);
    }

    public function test_force_https_false_overrides_an_https_app_url(): void
    {
        $url = $this->bootWith([
            'APP_URL' => 'https://sazmat.com',
            'FORCE_HTTPS' => 'false',
        ]);

        $this->assertSame('http://sazmat.com', $url);
    }

    /*
    |--------------------------------------------------------------------------
    | کمکی
    |--------------------------------------------------------------------------
    |
    | یک برنامه‌ی تازه با متغیرهای محیطی دلخواه بالا می‌آورد، یک
    | درخواستِ http به آن می‌بندد و آدرسی که برای صفحه‌ی اول تولید
    | می‌کند را برمی‌گرداند.
    |
    */
    private function bootWith(array $env): string
    {
        $previous = [];

        foreach ($env as $key => $value) {
            $previous[$key] = $_SERVER[$key] ?? null;
            $_SERVER[$key] = $_ENV[$key] = $value;
            putenv("{$key}={$value}");
        }

        try {
            $app = require base_path('bootstrap/app.php');
            $app->make(Kernel::class)->bootstrap();

            // درخواستی که سرورِ پشتِ CDN می‌بیند: بدون TLS.
            $request = Request::create('http://sazmat.com/', 'GET');
            $app->instance('request', $request);

            $generator = $app->make('url');
            $generator->setRequest($request);

            return $generator->route('home');
        } finally {
            foreach ($previous as $key => $value) {
                if ($value === null) {
                    unset($_SERVER[$key], $_ENV[$key]);
                    putenv($key);
                } else {
                    $_SERVER[$key] = $_ENV[$key] = $value;
                    putenv("{$key}={$value}");
                }
            }

            // ریشه‌ی URL برنامه‌ی اصلیِ تست نباید آلوده بماند.
            URL::forceScheme(
                str_starts_with((string) config('app.url'), 'https://') ? 'https' : 'http'
            );
        }
    }
}
