

@extends('layouts.app')
@section('title','ثبت‌نام')
@section('content')
<div class="max-w-md mx-auto px-4 py-12"><div class="card p-8"><h1 class="text-2xl font-black">ساخت حساب</h1>
<div class="seg mt-6"><button type="button" data-tab="email" class="reg-tab is-active">ثبت‌نام با ایمیل</button><button type="button" data-tab="mobile" class="reg-tab">ثبت‌نام با شماره</button></div>
<div id="email-reg-panel"><form method="POST" action="{{ route('register.store') }}" class="mt-6 space-y-4" data-gate>@csrf
<input name="username" value="{{ old('username') }}" class="w-full rounded-lg border" placeholder="نام کاربری یکتا" autocomplete="username" required minlength="3" maxlength="50">
<input name="email" type="email" value="{{ old('email') }}" class="w-full rounded-lg border" placeholder="ایمیل" autocomplete="email" required>
<div class="relative"><input id="register-password" name="password" type="password" class="w-full rounded-lg border pl-12" placeholder="رمز عبور" autocomplete="new-password" required minlength="8"><button type="button" data-toggle-password="register-password" class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500">👁</button></div>
<div class="relative"><input id="register-password-confirmation" name="password_confirmation" type="password" class="w-full rounded-lg border pl-12" placeholder="تکرار رمز عبور" autocomplete="new-password" required data-match="#register-password"><button type="button" data-toggle-password="register-password-confirmation" class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500">👁</button></div>
<p class="text-xs text-gray-500">نام کاربری باید یکتا باشد. برای رمز عبور حداقل ۸ کاراکتر را انتخاب کنید</p><button class="btn-primary w-full" data-gate-submit>ثبت‌نام</button></form></div>
<div id="mobile-reg-panel" class="hidden"><form method="POST" action="{{ route('register.mobile.request') }}" class="mt-6 space-y-4" data-gate>@csrf<input name="username" value="{{ old('username') }}" class="w-full rounded-lg border" placeholder="نام کاربری یکتا" autocomplete="username" required minlength="3" maxlength="50">@error('username')<div class="text-red-600 text-sm">{{ $message }}</div>@enderror<input name="mobile" inputmode="tel" autocomplete="tel" maxlength="14" value="{{ old('mobile') }}" data-digits-only data-allow-plus class="w-full rounded-lg border" placeholder="مثلاً ۰۹۱۲۱۲۳۴۵۶۷ یا +۹۸۹۱۲۱۲۳۴۵۶۷" required minlength="10">@error('mobile')<div class="text-red-600 text-sm">{{ $message }}</div>@enderror<button class="btn-primary w-full" data-gate-submit>ارسال کد تأیید و ساخت حساب</button><p class="text-xs text-gray-500">بعد از تأیید کد، حساب با همین نام کاربری ساخته می‌شود و دفعه‌های بعد اطلاعات آن باقی می‌ماند.</p></form></div>
<p class="text-sm mt-6">حساب دارید؟ <a class="text-blue-700" href="{{ route('login') }}">ورود</a></p></div></div>
@push('scripts')<script>document.addEventListener('DOMContentLoaded',function(){document.querySelectorAll('.reg-tab').forEach(b=>b.addEventListener('click',()=>{document.querySelectorAll('.reg-tab').forEach(x=>x.classList.remove('is-active'));b.classList.add('is-active');document.querySelectorAll('[id$="-reg-panel"]').forEach(x=>x.classList.add('hidden'));document.getElementById(b.dataset.tab+'-reg-panel').classList.remove('hidden')}));document.querySelectorAll('[data-toggle-password]').forEach(b=>b.addEventListener('click',()=>{const i=document.getElementById(b.dataset.togglePassword);i.type=i.type==='password'?'text':'password'}));
/*
 * اگر فرمی که کاربر فرستاده فرمِ «ثبت‌نام با شماره» بوده (چه خطا روی
 * فیلد شماره باشد چه فیلد نام کاربری)، صفحه باید همون تب رو باز نگه
 * داره، نه اینکه به‌طور پیش‌فرض برگرده روی تب ایمیل.
 */
@if(!is_null(old('mobile')))
const mobileRegTabButton=document.querySelector('[data-tab="mobile"].reg-tab');if(mobileRegTabButton){mobileRegTabButton.click();}
@endif
});</script>@endpush
@endsection