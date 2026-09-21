@extends('layouts.app')
@section('title','تکمیل سفارش')
@section('content')
<div class="container" style="padding-top:32px;padding-bottom:60px;">
    <h1>تکمیل سفارش</h1>
        <p style="color:var(--color-steel);margin-top:6px;">اطلاعات تحویل را بررسی کن و سپس پرداخت کن</p>

            <div class="sz-grid sz-grid-2" style="margin-top:26px;">
                    <div class="corner-card" style="padding:24px;"><h2 style="font-size:1.1rem;">اطلاعات تحویل</h2><form method="POST" action="{{ route('checkout.place') }}" style="margin-top:20px;display:grid;gap:16px;">@csrf<div class="field"><label for="phone">شماره تماس</label><input id="phone" name="phone" value="{{ old('phone',auth()->user()->mobile) }}" required maxlength="15" inputmode="numeric" pattern="[0-9]*" data-digits-only placeholder="0913•••••••">@error('phone')<div class="hint" style="color:var(--color-red);">{{ $message }}</div>@enderror</div><div class="field"><label for="address">آدرس کامل تحویل</label><textarea id="address" name="address" rows="6" required maxlength="1000" placeholder="استان، شهر، خیابان، پلاک...">{{ old('address') }}</textarea>@error('address')<div class="hint" style="color:var(--color-red);">{{ $message }}</div>@enderror</div><button class="btn btn-primary btn-block">ادامه به پرداخت · {{ number_format($total) }} تومان</button></form></div>
                            <div class="corner-card" style="padding:24px;"><h2 style="font-size:1.1rem;">خلاصه سفارش</h2><div style="margin-top:16px;">@foreach($items as $item)<div class="summary-row" style="padding:12px 0;border-bottom:1px solid var(--color-line);"><span>{{ $item['ad']->title }} × {{ $item['quantity'] }}</span><strong>{{ number_format($item['subtotal']) }}</strong></div>@endforeach</div><div class="summary-total"><span>مجموع</span><strong>{{ number_format($total) }} تومان</strong></div><a href="{{ route('cart.index') }}" style="color:var(--color-blueprint);font-weight:700;">بازگشت به سبد ←</a></div>
                                </div>
                                </div>
                                @endsection