@extends('layouts.app')
@section('title','جستجو | سازمت')
@section('content')
<div class="container" style="padding-top:32px;padding-bottom:60px;">
    <div class="section-head" style="margin-bottom:24px;"><div class="eyebrow">جستجوی سازمت</div><h1 style="margin-top:8px;">نتایج برای «{{ $search }}»</h1><p>{{ $total }} نتیجه مرتبط پیدا شد.</p></div>
    <form class="hero-search" action="{{ route('search') }}" method="GET" style="max-width:760px;margin-bottom:38px;"><div class="hero-search-field"><span data-icon="search"></span><input type="search" name="search" value="{{ $search }}" required aria-label="جستجو"></div><button class="btn btn-primary">جستجو</button></form>

    @if($products->count())<section style="margin-bottom:44px;"><div class="section-head" style="display:flex;justify-content:space-between;align-items:center;max-width:none;margin-bottom:18px;"><h2 style="font-size:1.2rem;">محصولات <span style="color:var(--color-steel-light);font-size:.85rem;">{{ $products->count() }} مورد</span></h2><a class="btn btn-ghost btn-sm" href="{{ route('products',['search'=>$search]) }}">همه محصولات ←</a></div><div class="sz-grid sz-grid-4">@foreach($products as $ad)<x-ad-card :ad="$ad"/>@endforeach</div></section>@endif

    @if($services->count())<section style="margin-bottom:44px;"><div class="section-head" style="display:flex;justify-content:space-between;align-items:center;max-width:none;margin-bottom:18px;"><h2 style="font-size:1.2rem;">خدمات <span style="color:var(--color-steel-light);font-size:.85rem;">{{ $services->count() }} مورد</span></h2><a class="btn btn-ghost btn-sm" href="{{ route('services',['search'=>$search]) }}">همه خدمات ←</a></div><div class="sz-grid sz-grid-4">@foreach($services as $ad)<x-ad-card :ad="$ad"/>@endforeach</div></section>@endif

    @if($sellers->count())<section><div class="section-head" style="max-width:none;margin-bottom:18px;"><h2 style="font-size:1.2rem;">فروشندگان و ارائه‌دهندگان</h2></div><div class="sz-grid sz-grid-4">@foreach($sellers as $seller)<a href="{{ route('seller.profile',$seller) }}" class="corner-card seller-search-card" style="padding:20px;text-decoration:none;"><div class="seller-avatar">{{ mb_substr($seller->username ?: $seller->name,0,1) }}</div><h3 style="margin-top:12px;">{{ $seller->username ?: $seller->name }}</h3><p style="color:var(--color-steel);margin-top:5px;">{{ $seller->active_ads_count }} آگهی فعال</p><span style="color:var(--color-blueprint);font-weight:700;display:block;margin-top:12px;">مشاهده پروفایل ←</span></a>@endforeach</div></section>@endif

    @if(!$total)<div class="empty-state"><span data-icon="search"></span><h3>چیزی پیدا نشد</h3><p style="color:var(--color-steel);">نام محصول، خدمت یا فروشنده دیگری را امتحان کن.</p></div>@endif
</div>
@endsection
