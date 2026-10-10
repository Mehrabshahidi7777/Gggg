@extends('layouts.app')
@section('title','پروفایل فروشنده | سازمت')
@section('content')
<div class="container" style="padding-top:32px;padding-bottom:60px;">
    <div class="corner-card seller-hero" style="padding:28px;">
        <div class="seller-avatar-lg">{{ mb_substr($user->username ?: $user->name,0,1) }}</div>
        <div style="flex:1;"><div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;"><h1 style="font-size:1.6rem;">{{ $user->username ?: $user->name }}</h1>@if($salesCount>0)<span class="seller-badge">✓ فروشنده فعال</span>@endif</div><p style="color:var(--color-steel);margin-top:8px;">عضو سازمت از {{ $user->created_at?->format('Y/m/d') ?? '—' }}</p></div>
        {{--
            «قلم تکمیل‌شده» فقط وقتی خرید آنلاین روشن است، و «دیدن شماره»
            فقط وقتی دست‌کم یک نفر دیده. صفر در هر دو به خریدار می‌گوید
            «کسی سراغ این فروشنده نرفته» - که برای فروشنده‌ی تازه‌وارد
            منصفانه نیست. چرایش در SellerProfileController.
        --}}
        <div class="seller-stats">@if($salesCount !== null)<div><strong>{{ $salesCount }}</strong><span>قلم تکمیل‌شده</span></div>@endif @if($contactViewers > 0)<div><strong>{{ $contactViewers }}</strong><span>نفر شماره را دیده‌اند</span></div>@endif<div><strong>{{ $user->ads()->approved()->count() }}</strong><span>آگهی فعال</span></div></div>
    </div>

    @if($products->count())<section class="section--tight" style="padding-bottom:30px;"><div class="section-head"><h2>محصولات فروشنده</h2></div><div class="sz-grid sz-grid-4">@foreach($products as $ad)<x-ad-card :ad="$ad"/>@endforeach</div><div style="margin-top:20px;">{{ $products->links() }}</div></section>@endif
    @if($services->count())<section class="section--tight" style="padding-top:30px;"><div class="section-head"><h2>خدمات فروشنده</h2></div><div class="sz-grid sz-grid-4">@foreach($services as $ad)<x-ad-card :ad="$ad"/>@endforeach</div></section>@endif
</div>
@endsection
