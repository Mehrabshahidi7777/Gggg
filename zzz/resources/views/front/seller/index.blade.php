@extends('layouts.app')
@section('title','پنل فروشنده')
@section('content')

<div class="container" style="padding-top:32px;padding-bottom:60px;">

    <div style="display:flex;justify-content:space-between;align-items:center;gap:16px;flex-wrap:wrap;margin-bottom:28px;">
        <div>
            <h1 style="margin-bottom:6px;">پنل ارائه‌دهنده</h1>
            <p style="color:var(--color-steel);">مدیریت محصولات، سفارش‌ها و اشتراک خدمات</p>
        </div>
        <a class="btn btn-navy" href="{{ route('ad.create') }}">افزودن آگهی</a>
    </div>

    <div class="bg-blue-50 text-blue-800 p-4 rounded-xl text-sm leading-7" style="margin-bottom:24px;">
        توجه: به‌ازای هر فروش موفق شما در سازمت، ۱٪ از مبلغ به‌عنوان کارمزد پلتفرم کسر می‌شود.
    </div>

    <div class="sz-grid sz-grid-4" style="margin-bottom:28px;">

        <div class="corner-card stat-card">
            <div class="label">محصولات</div>
            <div class="value">{{ $productAds->count() }}</div>
        </div>

        <div class="corner-card stat-card">
            <div class="label">خدمات</div>
            <div class="value">{{ $serviceAds->count() }}</div>
        </div>

        <div class="corner-card stat-card">
            <div class="label">اقلام فروخته‌شده</div>
            <div class="value">
                {{ auth()->user()->orderItems()->whereIn('status', ['paid','processing','shipped','completed'])->sum('quantity') }}
            </div>
        </div>

        <div class="corner-card stat-card">
            <div class="label">اشتراک خدمات</div>
            <div class="value" style="font-size:1.1rem;">{{ $subscription?->plan?->title ?? 'ندارید' }}</div>
        </div>

    </div>

    @if($serviceAds->count() || $subscription)
        <div class="corner-card" style="padding:24px;margin-bottom:24px;">

            <div style="display:flex;justify-content:space-between;align-items:center;">
                <h2 style="font-size:1.15rem;">خدمات</h2>
                <a href="{{ route('service.panel') }}" style="color:var(--color-blueprint);font-weight:700;font-size:0.9rem;">پنل خدمات</a>
            </div>

            @if($subscription)
                <p style="font-size:0.85rem;color:var(--color-steel);margin-top:8px;">
                    وضعیت اشتراک: {{ $subscription->isActive() ? 'فعال' : 'منقضی/تعویق' }}
                    — پایان: {{ $subscription->ends_at?->format('Y/m/d') }}
                </p>
            @endif

            <div class="sz-grid sz-grid-3" style="margin-top:20px;">
                @foreach($serviceAds as $ad)
                    <div class="corner-card" style="padding:16px;">
                        <div style="font-weight:800;">{{ $ad->title }}</div>
                        <div style="font-size:0.85rem;color:var(--color-steel-light);margin-top:6px;">{{ $ad->status_text }}</div>
                    </div>
                @endforeach
            </div>

        </div>
    @endif

    <div class="corner-card" style="padding:24px;">

        <h2 style="font-size:1.15rem;margin-bottom:16px;">سفارش‌های محصولات شما</h2>

        <div style="overflow:auto;">
            <table class="sz-table">
                <thead>
                    <tr>
                        <th>سفارش</th>
                        <th>خریدار</th>
                        <th>محصول</th>
                        <th>تعداد</th>
                        <th>مبلغ</th>
                        <th>وضعیت</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orderItems as $item)
                        <tr>
                            <td>{{ $item->order->order_number }}</td>
                            <td>{{ $item->order->buyer?->username ?: ($item->order->buyer?->name ?? 'حذف‌شده') }}</td>
                            <td>{{ $item->title }}</td>
                            <td>{{ $item->quantity }}</td>
                            <td>{{ number_format($item->subtotal) }}</td>
                            <td>{{ $item->status_text }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="text-align:center;padding:30px;color:var(--color-steel-light);">
                                هنوز فروشی برای محصولات شما ثبت نشده است.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div style="margin-top:20px;">
            {{ $orderItems->links() }}
        </div>

    </div>

</div>

@endsection
