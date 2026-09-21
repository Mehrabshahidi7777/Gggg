<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\ContactMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules\Password;

class PageController extends Controller
{
    public function about()
    {
        return view('front.about');
    }

    public function contact()
    {
        return view('front.contact');
    }

    public function contactStore(Request $r)
    {
        /*
        | ربات‌ها این فرم را پیدا کرده‌اند: از شش پیام رسیده، پنج تا
        | تبلیغاتِ خودکار بود.
        |
        | ⚠️ به ربات گفته نمی‌شود که گیر افتاده.
        |
        | همان پیام موفقیت برمی‌گردد، فقط چیزی ذخیره نمی‌شود. اگر
        | خطا بدهیم، نویسنده‌ی ربات می‌فهمد کجا گیر کرده و دورش
        | می‌زند؛ این‌طور فکر می‌کند کارش گرفته و سراغ کار دیگری
        | می‌رود.
        */
        if ($this->looksAutomated($r)) {
            return $this->contactAccepted($r);
        }

        /*
        | تلفن اجباری است، ایمیل نه.
        |
        | این سایت بازار مصالح ساختمانی است؛ خیلی از مخاطبانش ایمیل
        | ندارند یا نمی‌خواهند بدهند، ولی همه شماره دارند - و خودِ
        | سایت هم با تماس تلفنی کار می‌کند. اجباری‌کردن ایمیل فقط
        | اصطکاک بود، بی‌آنکه راه ارتباط بهتری بسازد.
        */
        $data = $r->validate([
            'name' => 'required|string|max:255',
            'phone' => ['required', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'subject' => 'nullable|string|max:255',
            'message' => 'required|string|max:5000',
        ], [
            'phone.required' => 'شماره تماس را وارد کنید تا بتوانیم جواب بدهیم.',
        ]);

        ContactMessage::create($data);

        return $this->contactAccepted($r);
    }

    /*
    | یک پاسخ، دو شکل.
    |
    | فرم صفحه‌ی «تماس با ما» معمولی ارسال می‌شود و پیام موفقیت را
    | در فلش می‌خواهد. حباب گفت‌وگو با fetch می‌فرستد و می‌خواهد بدون
    | رفرش، همان‌جا تشکر را نشان بدهد.
    |
    | خطاهای اعتبارسنجی را لازم نیست اینجا دست بزنیم: لاراول برای
    | درخواستی که JSON می‌خواهد خودش ۴۲۲ با فهرست خطاها برمی‌گرداند.
    */
    private function contactAccepted(Request $request)
    {
        $message = 'پیام شما با موفقیت ارسال شد.';

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => $message]);
        }

        return back()->with('success', $message);
    }

    /*
    |--------------------------------------------------------------------------
    | تله‌ی ربات
    |--------------------------------------------------------------------------
    |
    | بدون کپچا، چون کپچا را آدمِ واقعی هم باید حل کند و برای فرمی که
    | ماهی چند پیام واقعی می‌گیرد هزینه‌اش بیشتر از فایده‌اش است.
    |
    | دو نشانه که ربات را لو می‌دهد و آدم هرگز تولیدش نمی‌کند:
    |
    |   ۱. فیلدی که در صفحه دیده نمی‌شود ولی پر شده است. ربات فرم را
    |      از روی HTML پر می‌کند و نمی‌داند این یکی از چشم پنهان است.
    |
    |   ۲. فرمی که کمتر از سه ثانیه بعد از باز شدن ارسال شده. آدم در
    |      سه ثانیه نه نام می‌نویسد نه ایمیل نه متن پیام.
    |
    */
    private function looksAutomated(Request $request): bool
    {
        if (filled($request->input('website'))) {
            Log::info('contact form: honeypot filled');

            return true;
        }

        /*
        | زمانِ باز شدن فرم، رمزگذاری‌شده تا دست‌کاری‌شدنی نباشد.
        |
        | اگر نبود یا خوانده نشد، *مانع نمی‌شویم*: ممکن است صفحه از
        | کش مرورگر آمده باشد یا نسخه‌ی قدیمیِ فرم باشد، و مسدودکردن
        | یک مشتری واقعی خیلی بدتر از رد شدن یک اسپم است.
        */
        $token = $request->input('opened_at');

        if (! is_string($token) || $token === '') {
            return false;
        }

        try {
            $openedAt = (int) decrypt($token);
        } catch (\Throwable $e) {
            return false;
        }

        if (time() - $openedAt < 3) {
            Log::info('contact form: submitted too fast');

            return true;
        }

        return false;
    }

    /*
    | تمام دسته‌بندی‌های فعال سایت (بدون محدودیت ۸ تایی صفحه اصلی)
    */
    public function categories()
    {
        $productCategories = Category::where('type', 'product')
            ->where('is_active', true)
            ->withCount([
                'ads' => function ($query) {
                    $query->approved();
                },
            ])
            ->orderBy('name')
            ->get();

        $serviceCategories = Category::where('type', 'service')
            ->where('is_active', true)
            ->withCount([
                'ads' => function ($query) {
                    $query->approved();
                },
            ])
            ->orderBy('name')
            ->get();

        return view('front.categories', compact('productCategories', 'serviceCategories'));
    }

    public function profile()
    {
        /*
        | کاربرهایی که با شماره موبایل ثبت‌نام/وارد شده‌اند، ایمیل ندارند
        | و رمز عبورشان هم یک رشته‌ی تصادفی است که خودشان از آن خبر
        | ندارند (چون همیشه با پیامک وارد می‌شوند). برای همین بخش
        | «تغییر رمز عبور» برای این‌ها بی‌معنی است و نباید نشان داده شود.
        */
        $hasPasswordLogin = (bool) auth()->user()->email;

        return view('front.profile', compact('hasPasswordLogin'));
    }

    /*
    | فقط نام کاربری قابل تغییره — ایمیل و شماره موبایل فقط نمایشی هستن.
    */
    public function updateProfile(Request $r)
    {
        $data = $r->validate([
            'username' => [
                'required', 'string', 'min:3', 'max:50', 'alpha_dash',
                'unique:users,username,' . auth()->id(),
            ],
        ]);

        auth()->user()->update($data);

        return back()->with('success', 'نام کاربری شما به‌روزرسانی شد.');
    }

    public function updatePassword(Request $r)
    {
        /*
        | کاربرهایی که با موبایل وارد شده‌اند رمز عبور واقعی ندارند
        | (یک مقدار تصادفی و ناشناخته موقع ثبت‌نام برایشان ساخته شده)،
        | پس این عملیات برای آن‌ها معنی ندارد؛ حتی اگر کسی مستقیم به
        | این آدرس درخواست بفرستد باید همینجا رد بشود.
        */
        if (! auth()->user()->email) {
            return back()->withErrors([
                'current_password' => 'حساب شما با شماره موبایل ساخته شده و رمز عبور ندارد.',
            ]);
        }

        $data = $r->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'confirmed', Password::min(8)],
        ]);

        if (! Hash::check($data['current_password'], auth()->user()->password)) {
            return back()->withErrors(['current_password' => 'رمز عبور فعلی اشتباه است.']);
        }

        auth()->user()->update(['password' => $data['password']]);

        return back()->with('success', 'رمز عبور شما با موفقیت تغییر کرد.');
    }
}
