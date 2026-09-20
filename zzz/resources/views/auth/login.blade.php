@extends('layouts.app')
@section('title','ورود')
@section('content')

<div class="max-w-md mx-auto px-4 py-12"><div class="card p-8"><h1 class="text-2xl font-black">ورود</h1>

<div class="seg mt-6">
    <button type="button" data-tab="email" class="auth-tab is-active">
        ورود با ایمیل
    </button>

    <button type="button" data-tab="mobile" class="auth-tab">
        ورود با شماره
    </button>
</div>

<div id="email-panel" class="auth-panel">

<form method="POST" action="{{ route('login.store') }}" class="mt-6 space-y-4">
    @csrf

    <input
        name="email"
        type="email"
        value="{{ old('email') }}"
        class="w-full rounded-lg border"
        placeholder="ایمیل"
    >

    <div class="relative">
        <input
            id="login-password"
            name="password"
            type="password"
            class="w-full rounded-lg border pl-12"
            placeholder="رمز عبور"
        >

        <button
            type="button"
            data-toggle-password="login-password"
            class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500"
        >
            👁
        </button>
    </div>

    <label class="flex items-center gap-2 text-sm">
        <input type="checkbox" name="remember">
        مرا به خاطر بسپار
    </label>

    <button
        type="submit"
        id="email-login-button"
        class="btn-primary w-full"
    >
        ورود
    </button>
</form>

</div>

<div id="mobile-panel" class="auth-panel hidden">

<form
    method="POST"
    action="{{ route('login.mobile.request') }}"
    class="mt-6 space-y-4"
>
    @csrf

    <input
        name="mobile"
        inputmode="tel"
        autocomplete="tel"
        maxlength="14"
        dir="ltr"
        value="{{ old('mobile') }}"
        data-digits-only
        data-allow-plus
        required
        oninvalid="this.setCustomValidity('لطفا فیلد شماره را کامل کنید.')"
        oninput="this.setCustomValidity('')"
        class="w-full rounded-lg border text-left"
        placeholder="۰۹۱۳..."
    >

    @error('mobile')
        <div class="text-red-600 text-sm">{{ $message }}</div>
    @enderror

    <button class="btn-primary w-full">
        ارسال کد تأیید
    </button>

    <p class="text-xs text-gray-500">
        برای ورود با شماره فقط کد پیامکی لازم است و رمز عبور ندارد.
    </p>
</form>

</div>

<p class="text-sm mt-6">
    حساب ندارید؟
    <a class="text-blue-700" href="{{ route('register') }}">
        ثبت‌نام
    </a>
</p>

</div></div>

@push('scripts')

<script>
document.addEventListener('DOMContentLoaded', function () {

    /*
     * جابه‌جایی بین ورود با ایمیل و ورود با شماره
     */
    document.querySelectorAll('.auth-tab').forEach(function (button) {

        button.addEventListener('click', function () {

            /*
            | قبلاً اینجا opacity جابه‌جا می‌شد، نه حالت فعال. چون رنگِ
            | نارنجی روی کلاس تبِ اول چسبیده بود، هر کدام را که کلیک
            | می‌کردی باز هم تبِ اول رنگی می‌ماند.
            */
            document.querySelectorAll('.auth-tab').forEach(function (item) {
                item.classList.remove('is-active');
            });

            button.classList.add('is-active');

            document.querySelectorAll('.auth-panel').forEach(function (panel) {
                panel.classList.add('hidden');
            });

            const panel = document.getElementById(
                button.dataset.tab + '-panel'
            );

            if (panel) {
                panel.classList.remove('hidden');
            }
        });
    });

    /*
     * اگر خطای مربوط به فرم ورود با شماره وجود داشته باشد (مثلاً فیلد
     * شماره خالی مانده)، صفحه باید همان تب «ورود با شماره» را باز نگه
     * دارد، نه اینکه به‌طور پیش‌فرض برگردد روی تب ایمیل و کاربر فکر کند
     * خطا بی‌ربط یا گم شده است.
     */
    @if($errors->has('mobile') || old('mobile'))
        const mobileTabButton = document.querySelector('[data-tab="mobile"]');
        if (mobileTabButton) {
            mobileTabButton.click();
        }
    @endif


    /*
     * نمایش / مخفی کردن رمز عبور
     */
    document.querySelectorAll('[data-toggle-password]').forEach(function (button) {

        button.addEventListener('click', function () {

            const input = document.getElementById(
                button.dataset.togglePassword
            );

            if (!input) {
                return;
            }

            input.type =
                input.type === 'password'
                    ? 'text'
                    : 'password';
        });
    });


    /*
     * جلوگیری از ارسال دوباره فرم ورود با ایمیل
     *
     * بعد از اولین Submit:
     * - دکمه غیرفعال می‌شود
     * - متن دکمه تغییر می‌کند
     * - درخواست دوم ارسال نمی‌شود
     */
    const emailForm = document.querySelector('#email-panel form');
    const emailLoginButton = document.getElementById('email-login-button');

    if (emailForm && emailLoginButton) {

        emailForm.addEventListener('submit', function (event) {

            if (emailForm.dataset.submitting === '1') {
                event.preventDefault();
                return;
            }

            emailForm.dataset.submitting = '1';

            emailLoginButton.disabled = true;

            emailLoginButton.textContent = 'در حال ورود...';

            emailLoginButton.classList.add(
                'opacity-60',
                'cursor-not-allowed'
            );
        });
    }

});
</script>

@endpush

@endsection