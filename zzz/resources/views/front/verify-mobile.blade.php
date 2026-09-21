@extends('layouts.app')

@section('title', 'تأیید شماره موبایل')

{{--
|-------------------------------------------------------------------------
| چرا این صفحه وجود دارد
|-------------------------------------------------------------------------
|
| کاربری که با ایمیل ثبت‌نام کرده هیچ شماره‌ای ندارد. تا امروز هم
| هیچ جایی برای افزودنش نبود - پروفایل فقط نام کاربری را عوض می‌کند.
|
| اینجا وسطِ کار متوقفش می‌کنیم، پس اولین چیزی که می‌بیند باید
| *دلیل* باشد، نه یک فیلد خالی. کسی که نفهمد چرا شماره‌اش را
| می‌خواهیم، یا می‌رود یا شماره‌ی الکی می‌زند.
|
| ⚠️ نارنجیِ تو‌پُر فقط روی یک دکمه‌ی این صفحه می‌نشیند: کنش اصلی.
| در مرحله‌ی اول «ارسال کد»، در مرحله‌ی دوم «تأیید». دکمه‌ی
| «ارسال دوباره» خط‌دار است، نه پُر.
--}}

@section('content')
<div class="max-w-md mx-auto px-4 py-12">

    <div class="card p-8">

        @if(! $pending)

            {{-- مرحله‌ی اول: توضیح، بعد فیلد --}}

            <h1 class="text-2xl font-black">شماره‌ی موبایلت را تأیید کن</h1>

            <p class="text-sm text-gray-600 mt-3 leading-7">
                حساب شما با ایمیل ساخته شده و شماره‌ی موبایل ندارد.
                برای ثبت آگهی یک بار باید شماره‌ات را تأیید کنی.
            </p>

            <ul class="mt-5 space-y-3 text-sm text-gray-700 leading-7">
                <li class="flex gap-2">
                    <span aria-hidden="true">•</span>
                    <span>
                        <strong>قبل از تمام‌شدن اشتراک خبرت می‌کنیم.</strong>
                        بدون شماره، اشتراک بی‌صدا تمام می‌شود و آگهی‌هایت
                        از سایت برداشته می‌شوند.
                    </span>
                </li>
                <li class="flex gap-2">
                    <span aria-hidden="true">•</span>
                    <span>
                        <strong>راه برگشت به حسابت.</strong>
                        اگر رمزت را فراموش کنی، با همین شماره وارد می‌شوی.
                    </span>
                </li>
                <li class="flex gap-2">
                    <span aria-hidden="true">•</span>
                    <span>
                        <strong>در آگهی نمایش داده نمی‌شود.</strong>
                        شماره‌ای که مشتری‌ها می‌بینند همان است که خودت
                        داخل فرم آگهی می‌نویسی.
                    </span>
                </li>
            </ul>

            <form method="POST" action="{{ route('mobile.attach.request') }}"
                  class="mt-7 space-y-4" data-gate>
                @csrf

                <label class="block">
                    <span class="block text-sm font-bold mb-2">شماره موبایل</span>

                    <input name="mobile"
                           inputmode="numeric"
                           dir="ltr"
                           data-digits-only
                           autocomplete="tel"
                           maxlength="11"
                           required
                           value="{{ old('mobile') }}"
                           class="w-full rounded-lg border text-center text-lg"
                           placeholder="09121234567">
                </label>

                @error('mobile')
                    <p class="text-sm text-red-600">{{ $message }}</p>
                @enderror

                <button class="btn-primary w-full" data-gate-submit>
                    ارسال کد تأیید
                </button>
            </form>

        @else

            {{-- مرحله‌ی دوم: کد --}}

            <h1 class="text-2xl font-black">کد تأیید</h1>

            <p class="text-sm text-gray-600 mt-2">
                کد ارسال‌شده به <span dir="ltr">{{ $pending }}</span> را وارد کن.
            </p>

            <form method="POST" action="{{ route('mobile.attach.verify') }}"
                  class="mt-6 space-y-4" data-gate>
                @csrf

                <input name="code"
                       inputmode="numeric"
                       pattern="[0-9]*"
                       data-digits-only
                       autocomplete="one-time-code"
                       maxlength="6"
                       minlength="6"
                       required
                       class="w-full rounded-lg border text-center text-2xl tracking-[.5em]"
                       placeholder="------">

                @error('code')
                    <p class="text-sm text-red-600">{{ $message }}</p>
                @enderror

                <button class="btn-primary w-full" data-gate-submit>
                    تأیید و ادامه
                </button>
            </form>

            {{-- کنش دوم، پس خط‌دار است نه نارنجیِ پُر --}}
            <form method="POST" action="{{ route('mobile.attach.request') }}" class="mt-3">
                @csrf
                <input type="hidden" name="mobile" value="{{ $pending }}">

                <button class="btn btn-outline-navy w-full">
                    ارسال دوباره کد
                </button>
            </form>

            <p class="text-xs text-gray-400 mt-4 leading-6">
                کد تا ۳ دقیقه معتبر است. برای جلوگیری از ارسال پشت‌سرهم،
                فاصله‌ی درخواست دوباره ۹۰ ثانیه است.
            </p>

        @endif

    </div>
</div>
@endsection
