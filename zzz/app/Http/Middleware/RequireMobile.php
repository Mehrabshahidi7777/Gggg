<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| ثبت آگهی، بدون شماره‌ی تأییدشده نه
|--------------------------------------------------------------------------
|
| کسی که با موبایل ثبت‌نام کرده از اینجا بی‌درنگ رد می‌شود؛ این فقط
| برای حساب‌های ایمیلی است که هیچ شماره‌ای ندارند.
|
| ⚠️ مقصد در سشن می‌نشیند تا بعد از تأیید، کاربر به همان کاری که
| می‌خواست بکند برگردد - نه به صفحه‌ی خانه. کسی که وسط ثبت آگهی
| متوقف شده، اگر به خانه پرت شود همان‌جا رهایش می‌کند.
|
| فقط GET ذخیره می‌شود: برگرداندنِ کاربر به یک POST معنی ندارد.
|
*/
class RequireMobile
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if ($user && ! $user->mobile) {

            if ($request->isMethod('GET')) {
                $request->session()->put('url.intended', $request->fullUrl());
            }

            return redirect()->route('mobile.attach');
        }

        return $next($request);
    }
}
