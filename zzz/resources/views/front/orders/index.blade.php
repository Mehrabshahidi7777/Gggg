@extends('layouts.app')
@section('title','پنل مشتری')
@section('content')
<div class="container" style="padding-top:32px;padding-bottom:60px;">
    <div style="display:flex;justify-content:space-between;align-items:flex-end;gap:16px;flex-wrap:wrap;">
        <div><h1>پنل مشتری</h1><p style="color:var(--color-steel);margin-top:6px;">سفارش‌ها و وضعیت خریدهایت را از اینجا دنبال کن.</p></div>
        <a href="{{ route('products') }}" class="btn btn-navy">مشاهده محصولات</a>
    </div>

    <div class="sz-grid sz-grid-4" style="margin-top:28px;">
        <div class="corner-card stat-card"><div class="label">کل سفارش‌ها</div><div class="value">{{ $stats['total'] }}</div></div>
        <div class="corner-card stat-card"><div class="label">در حال پیگیری</div><div class="value">{{ $stats['active'] }}</div></div>
        <div class="corner-card stat-card"><div class="label">تکمیل‌شده</div><div class="value">{{ $stats['completed'] }}</div></div>
        <div class="corner-card stat-card"><div class="label">در انتظار پرداخت</div><div class="value">{{ $stats['pending'] }}</div></div>
    </div>

    <div class="corner-card" style="padding:24px;margin-top:28px;">
        <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;margin-bottom:16px;">
            <h2 style="font-size:1.15rem;">آخرین سفارش‌ها</h2>
            <a href="{{ route('cart.index') }}" style="color:var(--color-blueprint);font-weight:700;">سبد خرید ←</a>
        </div>
        <div style="overflow:auto;">
            <table class="sz-table">
                <thead><tr><th style="text-align:right;">شماره سفارش</th><th>مبلغ</th><th>وضعیت</th><th>تاریخ</th><th></th></tr></thead>
                <tbody>
                @forelse($orders as $order)
                    <tr><td>{{ $order->order_number }}</td><td>{{ number_format($order->total_amount) }} تومان</td><td>{{ $order->status_text }}</td><td>{{ $order->created_at->format('Y/m/d H:i') }}</td><td><a class="text-blue-700" href="{{ route('orders.show',$order) }}">جزئیات</a></td></tr>
                @empty
                    <tr><td colspan="5" style="text-align:center;padding:30px;color:var(--color-steel-light);">هنوز سفارشی ثبت نکرده‌ای. از محصولات دیدن کن و اولین خریدت را شروع کن.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div style="margin-top:20px;">{{ $orders->links() }}</div>
    </div>
</div>
@endsection
