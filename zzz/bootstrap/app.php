<?php

use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\EnsureOnlineCheckoutEnabled;
use App\Models\Ad;
use App\Models\ServiceSubscription;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Storage;

$app = Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )

    ->withMiddleware(function (Middleware $middleware): void {

        $middleware->alias([
            'admin' => AdminMiddleware::class,
            'checkout.enabled' => EnsureOnlineCheckoutEnabled::class,
        ]);

        /*
        |--------------------------------------------------------------------------
        | SEP Payment Callback
        |--------------------------------------------------------------------------
        */

        $middleware->validateCsrfTokens(except: [
            'payments/sep/order',
            'payments/sep/service',
            'payments/sep/product',
        ]);
    })

    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })

    ->withSchedule(function (Schedule $schedule): void {

        /*
        |--------------------------------------------------------------------------
        | Subscription Expiration (service & product)
        |--------------------------------------------------------------------------
        |
        | هر ساعت:
        |
        | 1. اشتراک‌های منقضی‌شده را expired می‌کنیم.
        | 2. خدمات کاربر را suspend می‌کنیم.
        |
        */

        $schedule->call(function (): void {

            $now = now();

            ServiceSubscription::query()
                ->where('status', 'active')
                ->whereNotNull('ends_at')
                ->where('ends_at', '<=', $now)
                ->chunkById(100, function ($subscriptions): void {

                    foreach ($subscriptions as $subscription) {

                        $subscription->update([
                            'status' => 'expired',
                            'grace_until' => $subscription
                                ->ends_at
                                ->copy()
                                ->addMonths(6),
                        ]);

                        Ad::query()
                            ->where('user_id', $subscription->user_id)
                            // این «service» هاردکد بود؛ چون همین جدول اشتراک
                            // حالا هم مال خدمت هم مال محصول است، باید دقیقاً
                            // همان نوعی که اشتراکش تمام شده تعلیق شود - نه
                            // همیشه service.
                            ->where('type', $subscription->type)
                            ->where('is_suspended', false)
                            ->update([
                                'is_suspended' => true,

                                /*
                                | شروع مهلت شش ماهه
                                | از پایان واقعی اشتراک است.
                                */
                                'suspended_at' => $subscription->ends_at,
                            ]);
                    }
                });

        })->hourly();


        /*
        |--------------------------------------------------------------------------
        | Permanent Cleanup (service & product)
        |--------------------------------------------------------------------------
        |
        | بعد از شش ماه:
        |
        | - فایل تصاویر حذف می‌شود.
        | - رکورد تصاویر حذف می‌شود.
        | - خود خدمت حذف می‌شود.
        |
        */

        $schedule->call(function (): void {

            $cutoff = now()->subMonths(6);

            Ad::query()
                ->with('images')
                // قبلاً اینجا where('type','service') هم بود؛ حذف شد چون
                // این پاک‌سازی باید بدون توجه به نوع آگهی، هر آگهیِ (خدمت
                // یا محصول) که ۶ ماه تعلیق مانده را شامل شود.
                ->where('is_suspended', true)
                ->whereNotNull('suspended_at')
                ->where('suspended_at', '<=', $cutoff)
                ->chunkById(100, function ($ads): void {

                    foreach ($ads as $ad) {

                        /*
                        |--------------------------------------------------------------------------
                        | Delete physical image files
                        |--------------------------------------------------------------------------
                        */

                        foreach ($ad->images as $image) {

                            if ($image->path) {
                                Storage::disk('public')->delete(
                                    $image->path
                                );
                            }
                        }

                        /*
                        |--------------------------------------------------------------------------
                        | Delete ad
                        |--------------------------------------------------------------------------
                        |
                        | ad_images به خاطر ON DELETE CASCADE
                        | از دیتابیس هم حذف می‌شوند.
                        |
                        */

                        $ad->delete();
                    }
                });


            /*
            |--------------------------------------------------------------------------
            | Delete old subscriptions
            |--------------------------------------------------------------------------
            */

            ServiceSubscription::query()
                ->whereIn('status', [
                    'expired',
                    'suspended',
                ])
                ->whereNotNull('grace_until')
                ->where('grace_until', '<=', now())
                ->delete();

        })->hourly();


        /*
        |--------------------------------------------------------------------------
        | Nightly cleanup
        |--------------------------------------------------------------------------
        |
        | فایل‌های تصویرِ بی‌صاحب، پرداخت‌های نیمه‌کاره، OTPهای منقضی و
        | پیش‌نویس‌های رهاشده. جزئیات در CleanupCommand.
        |
        | چون کرون‌جاب هاست از قبل هر دقیقه schedule:run را صدا می‌زند،
        | این کار بدون هیچ تنظیم اضافه‌ای هر شب ساعت ۳ اجرا می‌شود.
        |
        */
        $schedule->command('sazmat:cleanup')
            ->dailyAt('03:00')
            ->withoutOverlapping();

    })

    ->create();


/*
|--------------------------------------------------------------------------
| Laravel Public Path
|--------------------------------------------------------------------------
|
| پروژه Laravel داخل zzz است و DocumentRoot روی public_html قرار دارد.
|
*/

$app->usePublicPath(
    dirname(__DIR__) . '/../public_html'
);

return $app;