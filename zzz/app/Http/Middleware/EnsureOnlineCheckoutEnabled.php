<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/*
|--------------------------------------------------------------------------
| محافظ سبد خرید و تسویه‌حساب
|--------------------------------------------------------------------------
|
| وقتی marketplace.online_checkout غیرفعال است، هیچ مسیری که «سفارش
| جدید» می‌سازد نباید در دسترس باشد - نه از روی دکمه (که از ویوها
| برداشته شده) و نه از روی آدرس دستی یا فرمِ کش‌شده در مرورگر.
|
| به‌جای 404، کاربر به صفحه‌ی محصولات برمی‌گردد و دلیلش را می‌بیند،
| چون این یک صفحه‌ی ناموجود نیست بلکه قابلیتی است که عمداً خاموش شده.
|
*/
class EnsureOnlineCheckoutEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        if (config('marketplace.online_checkout')) {
            return $next($request);
        }

        $message = 'خرید آنلاین غیرفعال است. برای این آگهی مستقیماً با ارائه‌دهنده تماس بگیرید.';

        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => $message,
            ], 410);
        }

        return redirect()
            ->route('products')
            ->with('error', $message);
    }
}
