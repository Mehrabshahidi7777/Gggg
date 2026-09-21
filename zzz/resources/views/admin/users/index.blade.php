@extends('admin.layouts.app')

@section('content')

<h1 class="text-2xl font-black">کاربران</h1>

<div class="bg-white rounded-xl mt-5 overflow-auto">
    <table class="w-full text-sm">
        <thead>
            <tr>
                <th class="p-3 text-right">نام کاربری</th>
                <th>نام</th>
                <th>شماره و ایمیل</th>
                <th>آگهی</th>
                <th>مدیر</th>
                <th>عملیات</th>
            </tr>
        </thead>

        <tbody>
            @foreach($users as $u)
                <tr class="border-t">
                    <td class="p-3">
                        {{ $u->username ?: $u->name }}
                    </td>

                    <td>
                        {{ $u->name }}
                    </td>

                    {{--
                        ⚠️ قبلاً اینجا `$u->email ?: $u->mobile` بود، یعنی
                        تا ایمیل وجود داشت شماره اصلاً دیده نمی‌شد.

                        تا دیروز فرقی نمی‌کرد، چون هر کاربر فقط یکی از
                        این دو را داشت. حالا که کاربرِ ایمیلی برای ثبت
                        آگهی شماره‌اش را تأیید می‌کند، هر دو را دارد -
                        و آن `?:` دقیقاً همان چیزی را پنهان می‌کرد که
                        برای تماس گرفتن لازم است.

                        شماره اول می‌آید چون کارِ این سایت با تلفن
                        پیش می‌رود، نه با ایمیل.
                    --}}
                    <td>
                        @if($u->mobile)
                            <span dir="ltr" class="font-semibold">{{ $u->mobile }}</span>
                        @endif

                        @if($u->email)
                            <div dir="ltr" class="text-xs text-gray-500">{{ $u->email }}</div>
                        @endif

                        @unless($u->mobile || $u->email)
                            —
                        @endunless
                    </td>

                    <td>
                        {{ $u->ads_count }}
                    </td>

                    <td>
                        {{ $u->is_admin ? 'بله' : 'خیر' }}
                    </td>

                    <td>
                        <a
                            href="{{ route('admin.users.edit', $u) }}"
                            class="text-blue-700 hover:text-blue-900 font-semibold"
                        >
                            ویرایش
                        </a>

                        @if($u->id !== auth()->id())
                            <form
                                method="POST"
                                action="{{ route('admin.users.destroy', $u) }}"
                                onsubmit="return confirm('آیا مطمئن هستید که می‌خواهید این کاربر را برای همیشه حذف کنید؟');"
                                class="inline mr-3"
                            >
                                @csrf
                                @method('DELETE')

                                <button
                                    type="submit"
                                    class="text-red-600 hover:text-red-800 font-semibold"
                                >
                                    حذف کاربر
                                </button>
                            </form>
                        @else
                            <span class="text-gray-400 mr-3">
                                حساب فعلی
                            </span>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

<div class="mt-4">
    {{ $users->links() }}
</div>

@endsection