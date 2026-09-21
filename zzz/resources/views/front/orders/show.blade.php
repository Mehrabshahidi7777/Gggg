@extends('layouts.app')
@section('title','جزئیات سفارش')
@section('content')
<div class="container" style="padding-top:32px;padding-bottom:60px;">
    <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:20px;flex-wrap:wrap;">
        <div><a href="{{ route('orders.index') }}" style="color:var(--color-blueprint);font-weight:700;">← سفارش‌های من</a><h1 style="margin-top:10px;">سفارش {{ $order->order_number }}</h1><p style="color:var(--color-steel);margin-top:6px;">{{ $order->created_at->format('Y/m/d H:i') }}</p></div>
        <div style="font-size:1.25rem;font-weight:900;color:var(--color-blueprint);">{{ number_format($order->total_amount) }} تومان</div>
    </div>

    @php $steps=['paid'=>'پرداخت','processing'=>'آماده‌سازی','shipped'=>'ارسال','completed'=>'تکمیل']; $rank=['pending'=>0,'paid'=>1,'processing'=>2,'shipped'=>3,'completed'=>4,'cancelled'=>-1]; $current=$rank[$order->status] ?? 0; @endphp
    @if($order->status !== 'cancelled')
    <div class="corner-card" style="padding:24px;margin-top:26px;">
        <h2 style="font-size:1.05rem;margin-bottom:20px;">پیگیری سفارش</h2>
        <div class="order-timeline">
            @foreach($steps as $key=>$label)
                @php $active=($rank[$key] <= $current && $current > 0); @endphp
                <div class="order-step {{ $active ? 'is-active' : '' }}"><div class="order-step-dot">{{ $active ? '✓' : $loop->iteration }}</div><span>{{ $label }}</span></div>
            @endforeach
        </div>
        @if($order->status==='pending')<p style="color:var(--color-orange);margin-top:16px;">این سفارش هنوز پرداخت نشده است.</p>@endif
    </div>
    @else
        <div class="corner-card" style="padding:20px;margin-top:26px;border-color:var(--color-red);">این سفارش لغو شده است.</div>
    @endif

    <div class="sz-grid sz-grid-2" style="margin-top:24px;">
        <div class="corner-card" style="padding:24px;"><h2 style="font-size:1.05rem;margin-bottom:8px;">محصولات سفارش</h2>
            @foreach($order->items as $item)
                <div style="padding:18px 0;border-bottom:1px solid var(--color-line);">
                    <div style="display:flex;justify-content:space-between;gap:12px;"><div style="font-weight:800;">{{ $item->title }}</div><div style="font-weight:800;">{{ number_format($item->subtotal) }} تومان</div></div>
                    <div style="font-size:.88rem;color:var(--color-steel);margin-top:7px;">{{ $item->quantity }} × {{ number_format($item->unit_price) }} تومان · {{ $item->status_text }}</div>
                    <div style="font-size:.86rem;margin-top:7px;">فروشنده: <a href="{{ $item->seller ? route('seller.profile',$item->seller) : '#' }}" style="color:var(--color-blueprint);font-weight:700;">{{ $item->seller?->username ?: ($item->seller?->name ?? 'حذف‌شده') }}</a></div>
                </div>
            @endforeach
        </div>
        <div>
            <div class="corner-card" style="padding:24px;"><h2 style="font-size:1.05rem;">اطلاعات تحویل</h2><p style="margin-top:14px;line-height:1.9;">شماره تماس: {{ $order->phone }}</p><p style="line-height:1.9;">آدرس: {{ $order->address }}</p></div>
            <div class="corner-card" style="padding:24px;margin-top:18px;"><h2 style="font-size:1.05rem;">پرداخت</h2>@if($order->paid_at)<p style="color:var(--color-green);margin-top:12px;">پرداخت در {{ $order->paid_at->format('Y/m/d H:i') }} تأیید شده است.</p>@else<p style="color:var(--color-steel);margin-top:12px;">پرداخت هنوز تأیید نشده است.</p>@endif</div>
        </div>
    </div>
</div>
@endsection
