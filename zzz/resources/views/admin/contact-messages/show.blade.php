@extends('admin.layouts.app')

@section('title', 'مشاهده پیام')

@section('content')

<div class="flex items-center justify-between gap-3 mb-5">

    <div>
        <h1 class="text-2xl font-black">
            مشاهده پیام
        </h1>

        <p class="text-sm text-gray-500 mt-1">
            جزئیات پیام ارسال‌شده توسط کاربر
        </p>
    </div>

    <a
        href="{{ route('admin.contact-messages.index') }}"
        class="text-blue-700 hover:underline"
    >
        بازگشت به پیام‌ها
    </a>

</div>

<div class="bg-white rounded-xl shadow-sm p-6">

    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

        <div>
            <div class="text-xs text-gray-500 mb-1">
                نام
            </div>

            <div class="font-semibold">
                {{ $contactMessage->name }}
            </div>
        </div>

        <div>
            <div class="text-xs text-gray-500 mb-1">
                ایمیل
            </div>

            <div>
                {{ $contactMessage->email }}
            </div>
        </div>

        <div>
            <div class="text-xs text-gray-500 mb-1">
                شماره تماس
            </div>

            <div>
                {{ $contactMessage->phone ?: '—' }}
            </div>
        </div>

        <div>
            <div class="text-xs text-gray-500 mb-1">
                موضوع
            </div>

            <div>
                {{ $contactMessage->subject ?: 'بدون موضوع' }}
            </div>
        </div>

        <div>
            <div class="text-xs text-gray-500 mb-1">
                وضعیت
            </div>

            <div>
                @if($contactMessage->is_read)
                    <span class="text-gray-500">
                        خوانده شده
                    </span>
                @else
                    <span class="text-blue-700 font-semibold">
                        جدید
                    </span>
                @endif
            </div>
        </div>

        <div>
            <div class="text-xs text-gray-500 mb-1">
                تاریخ ارسال
            </div>

            <div>
                {{ $contactMessage->created_at?->format('Y/m/d H:i') }}
            </div>
        </div>

    </div>

    <div class="border-t mt-6 pt-6">

        <div class="text-xs text-gray-500 mb-2">
            متن پیام
        </div>

        <div class="bg-gray-50 rounded-lg p-5 leading-8 whitespace-pre-wrap">
            {{ $contactMessage->message }}
        </div>

    </div>

    <div class="border-t mt-6 pt-6 flex items-center gap-3">

        <a
            href="mailto:{{ $contactMessage->email }}"
            class="btn-primary"
        >
            پاسخ با ایمیل
        </a>

        <form
            method="POST"
            action="{{ route('admin.contact-messages.destroy', $contactMessage) }}"
            onsubmit="return confirm('آیا از حذف این پیام مطمئن هستید؟')"
        >
            @csrf
            @method('DELETE')

            <button
                type="submit"
                class="text-red-600 hover:underline"
            >
                حذف پیام
            </button>
        </form>

    </div>

</div>

@endsection