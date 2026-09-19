@extends('admin.layouts.app')

@section('content')

<h1 class="text-2xl font-black">کاربران</h1>

<div class="bg-white rounded-xl mt-5 overflow-auto">
    <table class="w-full text-sm">
        <thead>
            <tr>
                <th class="p-3 text-right">نام کاربری</th>
                <th>نام</th>
                <th>ایمیل/شماره</th>
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

                    <td>
                        {{ $u->email ?: $u->mobile ?: '—' }}
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