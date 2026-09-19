@extends('layouts.app')
@section('title','پروفایل فروشنده | سازمت')
@section('content')
<div class="container" style="padding-top:32px;padding-bottom:60px;">
    <div class="corner-card seller-hero" style="padding:28px;">
        <div class="seller-avatar-lg">{{ mb_substr($user->username ?: $user->name,0,1) }}</div>
        <div style="flex:1;"><div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;"><h1 style="font-size:1.6rem;">{{ $user->username ?: $user->name }}</h1>@if($salesCount>0)<span class="seller-badge">✓ فروشنده فعال</span>@endif</div><p style="color:var(--color-steel);margin-top:8px;">عضو سازمت از {{ $user->created_at?->format('Y/m/d') ?? '—' }}</p></div>
        <div class="seller-stats"><div><strong>{{ $salesCount }}</strong><span>قلم تکمیل‌شده</span></div><div><strong>{{ $user->ads()->approved()->count() }}</strong><span>آگهی فعال</span></div></div>
    </div>

    @if($products->count())<section class="section--tight" style="padding-bottom:30px;"><div class="section-head"><h2>محصولات فروشنده</h2></div><div class="sz-grid sz-grid-4">@foreach($products as $ad)<x-ad-card :ad="$ad"/>@endforeach</div><div style="margin-top:20px;">{{ $products->links() }}</div></section>@endif
    @if($services->count())<section class="section--tight" style="padding-top:30px;"><div class="section-head"><h2>خدمات فروشنده</h2></div><div class="sz-grid sz-grid-4">@foreach($services as $ad)<x-ad-card :ad="$ad"/>@endforeach</div></section>@endif
</div>
@endsection
