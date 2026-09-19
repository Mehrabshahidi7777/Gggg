@extends('admin.layouts.app')

@section('title', 'پیام‌های تماس با ما')

@section('content')

<div class="flex items-center justify-between gap-3 mb-5">
    <div>
        <h1 class="text-2xl font-black">پیام‌های تماس با ما</h1>

        <p class="text-sm text-gray-500 mt-1">
            پیام‌های ارسال‌شده توسط کاربران سایت
        </p>
    </div>
</div>

<div class="bg-white rounded-xl overflow-auto shadow-sm">

    <table class="w-full text-sm">

        <thead class="bg-gray-50">
            <tr>
                <th class="p-3 text-right whitespace-nowrap">
                    نام
                </th>

                <th class="p-3 text-right whitespace-nowrap">
                    ایمیل
                </th>

                <th class="p-3 text-right whitespace-nowrap">
                    شماره
                </th>

                <th class="p-3 text-right whitespace-nowrap">
                    موضوع
                </th>

                <th class="p-3 text-right whitespace-nowrap">
                    وضعیت
                </th>

                <th class="p-3 text-right whitespace-nowrap">
                    تاریخ
                </th>

                <th class="p-3 text-right">
                </th>
            </tr>
        </thead>

        <tbody>

            @forelse($messages as $message)

                <tr class="border-t">

                    <td class="p-3">
                        {{ $message->name }}
                    </td>

                    <td class="p-3">
                        {{ $message->email }}
                    </td>

                    <td class="p-3">
                        {{ $message->phone ?: '—' }}
                    </td>

                    <td class="p-3">
                        {{ $message->subject ?: 'بدون موضوع' }}
                    </td>

                    <td class="p-3">

                        @if($message->is_read)

                            <span class="text-gray-500">
                                خوانده شده
                            </span>

                        @else

                            <span class="text-blue-700 font-semibold">
                                جدید
                            </span>

                        @endif

                    </td>

                    <td class="p-3 whitespace-nowrap text-gray-500">
                        {{ $message->created_at?->format('Y/m/d H:i') }}
                    </td>

                    <td class="p-3 whitespace-nowrap">

                        <a
                            href="{{ route('admin.contact-messages.show', $message) }}"
                            class="text-blue-700 hover:underline"
                        >
                            مشاهده
                        </a>

                        <form
                            method="POST"
                            action="{{ route('admin.contact-messages.destroy', $message) }}"
                            class="inline"
                            onsubmit="return confirm('آیا از حذف این پیام مطمئن هستید؟')"
                        >
                            @csrf
                            @method('DELETE')

                            <button
                                type="submit"
                                class="text-red-600 hover:underline mr-3"
                            >
                                حذف
                            </button>
                        </form>

                    </td>

                </tr>

            @empty

                <tr>
                    <td
                        colspan="7"
                        class="p-8 text-center text-gray-500"
                    >
                        هنوز هیچ پیامی دریافت نشده است.
                    </td>
                </tr>

            @endforelse

        </tbody>

    </table>

</div>

<div class="mt-4">
    {{ $messages->links() }}
</div>

@endsection