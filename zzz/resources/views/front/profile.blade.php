@extends('layouts.app')
@section('title','پنل کاربری')
@section('content')

<div class="container" style="max-width:640px;padding-top:44px;padding-bottom:60px;">

    <h1 style="margin-bottom:6px;">پنل کاربری</h1>
    <p style="color:var(--color-steel);margin-bottom:32px;">اطلاعات حساب و رمز عبورت رو از همین‌جا مدیریت کن.</p>

    {{-- اطلاعات حساب (فقط نمایشی) --}}
    <div class="corner-card" style="padding:26px 28px;margin-bottom:24px;">
        <h3 style="margin-bottom:16px;">اطلاعات حساب</h3>

        <div style="display:flex;justify-content:space-between;padding:10px 0;border-bottom:1px solid var(--color-line);">
            <span style="color:var(--color-steel);">ایمیل</span>
            <span style="font-weight:700;">{{ auth()->user()->email ?: '—' }}</span>
        </div>

        <div style="display:flex;justify-content:space-between;padding:10px 0;">
            <span style="color:var(--color-steel);">شماره موبایل</span>
            <span style="font-weight:700;">{{ auth()->user()->mobile ?: '—' }}</span>
        </div>
    </div>

    {{-- تغییر نام کاربری --}}
    <div class="corner-card" style="padding:26px 28px;margin-bottom:24px;">
        <h3 style="margin-bottom:18px;">نام کاربری</h3>

        <form method="POST" action="{{ route('profile.update') }}">
            @csrf
            @method('PUT')

            <div class="field">
                <label for="username">نام کاربری</label>
                <input
                    id="username"
                    name="username"
                    value="{{ old('username', auth()->user()->username) }}"
                >
                @error('username')
                    <div class="hint" style="color:var(--color-red);">{{ $message }}</div>
                @enderror
            </div>

            <button class="btn btn-navy" type="submit">ذخیره نام کاربری</button>
        </form>
    </div>

    {{-- تغییر رمز عبور — فقط برای کاربرهایی که با ایمیل/رمز وارد می‌شوند --}}
    @if($hasPasswordLogin)
        <div class="corner-card" style="padding:26px 28px;">
            <h3 style="margin-bottom:18px;">تغییر رمز عبور</h3>

            <form method="POST" action="{{ route('profile.password.update') }}">
                @csrf
                @method('PUT')

                <div class="field">
                    <label for="current_password">رمز عبور فعلی</label>
                    <input id="current_password" name="current_password" type="password">
                    @error('current_password')
                        <div class="hint" style="color:var(--color-red);">{{ $message }}</div>
                    @enderror
                </div>

                <div class="field">
                    <label for="password">رمز عبور جدید</label>
                    <input id="password" name="password" type="password">
                    @error('password')
                        <div class="hint" style="color:var(--color-red);">{{ $message }}</div>
                    @enderror
                </div>

                <div class="field">
                    <label for="password_confirmation">تکرار رمز عبور جدید</label>
                    <input id="password_confirmation" name="password_confirmation" type="password">
                </div>

                <button class="btn btn-navy" type="submit">تغییر رمز عبور</button>
            </form>
        </div>
        <p style="color:var(--color-steel);margin-top:14px;">ورود موفقیت‌آمیز بود.</p>
    @else
        <p style="color:var(--color-steel);">ورود با شماره موبایل موفقیت‌آمیز بود.</p>
    @endif

    <div style="margin-top:24px;">
        <a href="{{ route('orders.index') }}" style="color:var(--color-blueprint);font-weight:700;">
            مشاهده سفارش‌های من ←
        </a>
    </div>

</div>

@endsection
