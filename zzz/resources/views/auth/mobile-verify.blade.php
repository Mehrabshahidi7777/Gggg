@extends('layouts.app')

@section('title', 'تأیید شماره موبایل')

@section('content')
<div class="max-w-md mx-auto px-4 py-16">
    <div class="card p-8">
        <h1 class="text-2xl font-black">کد تأیید</h1>

        <p class="text-sm text-gray-500 mt-2">
            کد ارسال‌شده به {{ $mobile }} را وارد کنید.
        </p>

        <form method="POST"
              action="{{ route('mobile.verify.store') }}"
              class="mt-6 space-y-4"
              data-gate>

            @csrf

            <input
                name="code"
                inputmode="numeric"
                pattern="[0-9]*"
                data-digits-only
                autocomplete="one-time-code"
                maxlength="6"
                minlength="6"
                required
                class="w-full rounded-lg border text-center text-2xl tracking-[.5em]"
                placeholder="------"
            >

            <button class="btn-primary w-full" data-gate-submit>
                تأیید و ادامه
            </button>
        </form>

        <form method="POST"
              action="{{ $purpose === 'register' ? route('register.mobile.request') : route('login.mobile.request') }}"
              class="mt-3">

            @csrf

            @if($purpose === 'register')
                <input
                    type="hidden"
                    name="username"
                    value="{{ session('mobile_register_username') }}"
                >
            @endif

            <input
                type="hidden"
                name="mobile"
                value="{{ $mobile }}"
            >

            <button class="w-full text-sm text-blue-700">
                ارسال دوباره کد
            </button>
        </form>

        <p class="text-xs text-gray-400 mt-4">
            کد تا ۳ دقیقه معتبر است و برای جلوگیری از ارسال‌های پشت‌سرهم،
            فاصله درخواست مجدد ۹۰ ثانیه است.
        </p>
    </div>
</div>
@endsection