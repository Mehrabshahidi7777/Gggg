<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\LoginOtp;
use App\Models\User;
use App\Services\AmootSmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use RuntimeException;

/*
|--------------------------------------------------------------------------
| افزودن شماره موبایل به حسابی که ندارد
|--------------------------------------------------------------------------
|
| ثبت‌نام دو راه دارد: ایمیل یا موبایل. راهِ ایمیل هیچ شماره‌ای
| نمی‌گیرد و پروفایل هم فقط نام کاربری را عوض می‌کند - یعنی چنین
| کاربری تا امروز هیچ راهی برای ثبت شماره نداشت.
|
| این سه چیز را می‌شکست:
|
|   ۱. مشتری نمی‌توانست به او زنگ بزند (شماره‌ی آگهی جداست، ولی
|      خودِ سایت هیچ راهی برای رسیدن به او نداشت)
|   ۲. یادآوری تمدید اشتراک بی‌صدا رد می‌شد، و آگهی‌هایش بی‌خبر
|      تعلیق می‌شد
|   ۳. اگر رمزش را فراموش می‌کرد، راه برگشتی نبود
|
| پس قبل از ثبت اولین آگهی، شماره گرفته و با کد تأیید می‌شود.
|
| ⚠️ چرا تأیید، و نه یک فیلد ساده؟
|
| شماره‌ی تأییدنشده بدتر از نداشتنِ شماره است: سیستم فکر می‌کند
| راهی برای رسیدن به کاربر دارد، پیامک‌ها به شماره‌ی غلط (یا به
| شماره‌ی یک آدم بی‌خبر) می‌روند، و کسی متوجه نمی‌شود.
|
| همان جدول login_otps استفاده می‌شود، فقط با purpose تازه. ستون
| purpose از نوع string است، پس هیچ تغییر دیتابیسی لازم نیست.
|
*/
class MobileAttachController extends Controller
{
    private const PURPOSE = 'attach';

    public function show(Request $request)
    {
        /*
        | اگر از قبل شماره دارد، اینجا کاری ندارد. مقصدِ ذخیره‌شده
        | (معمولاً صفحه‌ی ثبت آگهی) همان چیزی است که او را به اینجا
        | فرستاده بود.
        */
        if (auth()->user()->mobile) {
            return redirect()->intended(route('ad.create'));
        }

        return view('front.verify-mobile', [
            'pending' => $request->session()->get('mobile_attach_pending'),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | مرحله‌ی اول: گرفتن شماره و فرستادن کد
    |--------------------------------------------------------------------------
    */
    public function request(Request $request, AmootSmsService $sms)
    {
        $mobile = normalize_mobile($request->input('mobile', ''));

        validator(['mobile' => $mobile], [
            'mobile' => ['required', 'regex:/^09\d{9}$/'],
        ], [
            'mobile.required' => 'شماره موبایل را وارد کنید.',
            'mobile.regex' => 'شماره موبایل را درست وارد کنید؛ مثل ۰۹۱۲۱۲۳۴۵۶۷.',
        ])->validate();

        /*
        | ستون mobile یکتاست. بدون این بررسی، ذخیره‌ی نهایی با خطای
        | دیتابیس می‌افتاد - و کاربر بعد از گرفتن و واردکردن کد، یک
        | صفحه‌ی ۵۰۰ می‌دید.
        */
        if (User::where('mobile', $mobile)->whereKeyNot(auth()->id())->exists()) {
            return back()->withErrors([
                'mobile' => 'این شماره قبلاً برای حساب دیگری ثبت شده است.',
            ])->withInput();
        }

        $key = 'otp:attach:' . $request->ip() . ':' . $mobile;

        if (RateLimiter::tooManyAttempts($key, 5)) {
            return back()->withErrors([
                'mobile' => 'تعداد درخواست‌ها زیاد است. چند دقیقه بعد دوباره تلاش کنید.',
            ])->withInput();
        }

        RateLimiter::hit($key, 90);

        LoginOtp::where('mobile', $mobile)
            ->where('purpose', self::PURPOSE)
            ->whereNull('verified_at')
            ->delete();

        $code = (string) random_int(100000, 999999);

        $otp = LoginOtp::create([
            'mobile' => $mobile,
            'code_hash' => Hash::make($code),
            'purpose' => self::PURPOSE,
            'expires_at' => now()->addMinutes(3),
        ]);

        try {
            $sms->sendOtp($mobile, $code);
        } catch (RuntimeException $e) {
            /*
            | اگر پیامک نرفت، کد هم نباید بماند: کاربر کدی ندارد که
            | واردش کند، و ماندنش فقط جدول را شلوغ می‌کند.
            */
            $otp->delete();

            return back()->withErrors(['mobile' => $e->getMessage()])->withInput();
        }

        $request->session()->put('mobile_attach_pending', $mobile);

        return redirect()
            ->route('mobile.attach')
            ->with('success', 'کد تأیید برای ' . $mobile . ' ارسال شد.');
    }

    /*
    |--------------------------------------------------------------------------
    | مرحله‌ی دوم: بررسی کد و ذخیره‌ی شماره
    |--------------------------------------------------------------------------
    */
    public function verify(Request $request)
    {
        $mobile = $request->session()->get('mobile_attach_pending');

        if (! $mobile) {
            return redirect()->route('mobile.attach');
        }

        $data = $request->validate(
            ['code' => 'required|digits:6'],
            ['code.digits' => 'کد تأیید شش رقم است.']
        );

        $otp = LoginOtp::where('mobile', $mobile)
            ->where('purpose', self::PURPOSE)
            ->whereNull('verified_at')
            ->latest()
            ->first();

        if (! $otp || $otp->expires_at->isPast()) {
            return back()->withErrors(['code' => 'کد تأیید منقضی شده است. دوباره کد بگیرید.']);
        }

        if ($otp->attempts >= 5) {
            return back()->withErrors(['code' => 'تعداد تلاش‌های مجاز تمام شد. دوباره کد بگیرید.']);
        }

        $otp->increment('attempts');

        if (! Hash::check($data['code'], $otp->code_hash)) {
            return back()->withErrors(['code' => 'کد تأیید اشتباه است.']);
        }

        /*
        | ⚠️ یک بار دیگر یکتایی بررسی می‌شود.
        |
        | بین فرستادن کد و واردکردنش سه دقیقه فاصله است؛ ممکن است در
        | همان فاصله کسِ دیگری همین شماره را ثبت کرده باشد.
        */
        if (User::where('mobile', $mobile)->whereKeyNot(auth()->id())->exists()) {
            $request->session()->forget('mobile_attach_pending');

            return redirect()->route('mobile.attach')->withErrors([
                'mobile' => 'این شماره در همین فاصله برای حساب دیگری ثبت شد.',
            ]);
        }

        $otp->update(['verified_at' => now()]);

        auth()->user()->forceFill(['mobile' => $mobile])->save();

        $request->session()->forget('mobile_attach_pending');

        return redirect()
            ->intended(route('ad.create'))
            ->with('success', 'شماره‌ی شما تأیید شد. حالا می‌توانید آگهی ثبت کنید.');
    }
}
