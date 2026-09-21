@extends('admin.layouts.app')

{{--
    ⚠️ این صفحه فقط ایمیل را نشان می‌داد.

    کاربری که با موبایل ثبت‌نام کرده ایمیل ندارد، پس اینجا یک خط
    خالی می‌دید - و شماره‌اش، که تنها راه رسیدن به اوست، هیچ‌جا
    نبود.
--}}

@section('content')

<div class="bg-white p-6 rounded-xl">

    <h1 class="text-2xl font-black">{{ $user->name }}</h1>

    @if($user->username && $user->username !== $user->name)
        <p class="text-sm text-gray-500 mt-1">{{ $user->username }}</p>
    @endif

    <dl class="mt-5 space-y-3 text-sm">

        <div>
            <dt class="text-gray-500">شماره موبایل</dt>
            <dd dir="ltr" class="font-semibold">{{ $user->mobile ?: '—' }}</dd>
        </div>

        <div>
            <dt class="text-gray-500">ایمیل</dt>
            <dd dir="ltr">{{ $user->email ?: '—' }}</dd>
        </div>

        <div>
            <dt class="text-gray-500">تاریخ ثبت‌نام</dt>
            <dd>{{ $user->created_at?->format('Y/m/d H:i') ?: '—' }}</dd>
        </div>

    </dl>

</div>

@endsection
