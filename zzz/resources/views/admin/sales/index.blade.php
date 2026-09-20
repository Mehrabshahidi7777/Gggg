@extends('admin.layouts.app')
@section('title','گزارش فروش فروشندگان')
@section('content')
<div class="flex flex-wrap items-center justify-between gap-4">
    <div>
        <h1 class="text-2xl font-black">گزارش فروش فروشندگان</h1>
        <p class="text-sm text-gray-500 mt-2">فروش‌های پرداخت‌شده به‌صورت دوره‌های ثابت دوماهه نگهداری و نمایش داده می‌شوند؛ دوره‌های قبلی حذف نمی‌شوند.</p>
    </div>
</div>

<form method="GET" class="bg-white p-4 rounded-xl mt-5 flex flex-wrap gap-3 items-center">
    <select name="seller_id" class="rounded-lg border min-w-64">
        <option value="">همه فروشندگان</option>
        @foreach($allSellers as $seller)
            <option value="{{ $seller->id }}" @selected($sellerId === $seller->id)>
                {{ $seller->username ?: $seller->name }}{{ $seller->mobile ? ' — '.$seller->mobile : '' }}
            </option>
        @endforeach
    </select>
    <button class="btn-primary">نمایش گزارش</button>
    @if($sellerId)<a href="{{ route('admin.sales.index') }}" class="btn btn-outline-navy btn-sm">حذف فیلتر</a>@endif
</form>

@if($currentPeriod)
<div class="grid md:grid-cols-3 gap-4 mt-6">
    <div class="bg-white rounded-xl p-5"><div class="text-sm text-gray-500">فروش دوره فعلی</div><div class="text-2xl font-black mt-2">{{ number_format($currentPeriod['gross_amount']) }} تومان</div></div>
    <div class="bg-white rounded-xl p-5"><div class="text-sm text-gray-500">تعداد سفارش‌های دوره فعلی</div><div class="text-2xl font-black mt-2">{{ number_format($currentPeriod['orders_count']) }}</div></div>
    <div class="bg-white rounded-xl p-5"><div class="text-sm text-gray-500">اقلام فروخته‌شده دوره فعلی</div><div class="text-2xl font-black mt-2">{{ number_format($currentPeriod['quantity']) }}</div></div>
</div>
@endif

<div class="space-y-6 mt-7">
@forelse($periods as $period)
    <section class="bg-white rounded-xl overflow-hidden">
        <div class="p-5 border-b flex flex-wrap justify-between gap-3">
            <div><h2 class="font-black text-xl">دوره {{ $period['label'] }}</h2><p class="text-sm text-gray-500 mt-1">تاریخچه این دوره با شروع دوره‌های بعدی حذف نمی‌شود.</p></div>
            <div class="text-left"><div class="font-black">{{ number_format($period['gross_amount']) }} تومان</div><div class="text-xs text-gray-500">{{ number_format($period['orders_count']) }} سفارش · {{ number_format($period['quantity']) }} قلم</div></div>
        </div>
        <div class="overflow-auto">
            <table class="w-full text-sm">
                <thead><tr class="border-b"><th class="p-3 text-right">فروشنده</th><th>شماره تماس</th><th>شماره شبا</th><th>سفارش</th><th>تعداد</th><th>مبلغ فروش</th></tr></thead>
                <tbody>
                @foreach($period['sellers'] as $row)
                    <tr class="border-b last:border-0">
                        <td class="p-3 font-bold">{{ $row['seller']?->username ?: ($row['seller']?->name ?? 'حذف‌شده') }}</td>
                        <td>{{ $row['seller']?->mobile ?: '—' }}</td>
                        <td>{{ $row['card_number'] ? 'IR'.$row['card_number'] : '—' }}</td>
                        <td>{{ number_format($row['orders_count']) }}</td>
                        <td>{{ number_format($row['quantity']) }}</td>
                        <td class="font-black">{{ number_format($row['gross_amount']) }} تومان</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </section>
@empty
    <div class="bg-white rounded-xl p-8 text-center text-gray-500">هنوز فروش پرداخت‌شده‌ای برای گزارش وجود ندارد.</div>
@endforelse
</div>
@endsection
