@extends('admin.layouts.app')

@section('content')

<div class="flex justify-between items-center flex-wrap gap-3">
    <h1 class="text-2xl font-black">پلن‌های اشتراک</h1>
    <a class="btn-primary" href="{{ route('admin.plans.create', ['type' => $type]) }}">پلن جدید</a>
</div>

<div class="flex gap-2 mt-5">
    <a
        href="{{ route('admin.plans.index', ['type' => 'service']) }}"
        style="display:inline-block;padding:8px 16px;border-radius:8px;font-size:0.875rem;font-weight:700;text-decoration:none;{{ $type === 'service' ? 'background:#2563eb;color:#ffffff;' : 'background:#ffffff;color:#374151;border:1px solid #d1d5db;' }}"
    >
        پلن‌های خدمات
    </a>
    <a
        href="{{ route('admin.plans.index', ['type' => 'product']) }}"
        style="display:inline-block;padding:8px 16px;border-radius:8px;font-size:0.875rem;font-weight:700;text-decoration:none;{{ $type === 'product' ? 'background:#2563eb;color:#ffffff;' : 'background:#ffffff;color:#374151;border:1px solid #d1d5db;' }}"
    >
        پلن‌های محصول
    </a>
</div>

<div class="bg-white rounded-xl mt-5 overflow-auto">
    <table class="w-full text-sm">
        <thead>
            <tr>
                <th class="p-3">عنوان</th>
                <th>مدت (ماه)</th>
                <th>قیمت (تومان)</th>
                <th>ترتیب نمایش</th>
                <th>فعال</th>
                <th>تعداد خرید</th>
                <th>عملیات</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($plans as $plan)
                <tr class="border-t">
                    <td class="p-3">{{ $plan->title }}</td>
                    <td>{{ $plan->months }}</td>
                    <td>{{ number_format((float) $plan->price) }}</td>
                    <td>{{ $plan->sort_order }}</td>
                    <td>{{ $plan->is_active ? 'بله' : 'خیر' }}</td>
                    <td>{{ $plan->subscriptions_count }}</td>
                    <td>
                        <a class="text-blue-700" href="{{ route('admin.plans.edit', $plan) }}">ویرایش</a>

                        <form class="inline" method="POST" action="{{ route('admin.plans.destroy', $plan) }}" onsubmit="return confirm('حذف شود؟');">
                            @csrf
                            @method('DELETE')
                            <button class="text-red-600 mr-2">حذف</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr class="border-t">
                    <td class="p-3 text-gray-400" colspan="7">هنوز پلنی برای این نوع ثبت نشده.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@endsection
