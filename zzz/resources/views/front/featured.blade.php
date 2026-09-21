@extends('layouts.app')
@section('title','آگهی‌های ویژه | سازمت')
@section('meta_description','همه‌ی محصولات و خدمات ویژه‌ی سازمت، بازار آنلاین صنعت ساختمان.')

@section('content')

<section class="section section--tight">
    <div class="container">

        <div class="section-head section-head--center">
            <h1>آگهی‌های ویژه</h1>
            <p>محصولات و خدماتی که ارائه‌دهنده‌هایشان برای دیده‌شدن بیشتر انتخابشان کرده‌اند.</p>
        </div>

        {{--
            محصولات ویژه

            هر بخش صفحه‌بندی خودش را دارد (نام صفحه در آدرس: products و
            services)، وگرنه رفتن به صفحه‌ی دوم محصولات، خدمات را هم
            جابه‌جا می‌کرد.
        --}}
        <div class="featured-head">
            <h2>محصولات ویژه</h2>
            <a href="{{ route('products') }}" class="btn btn-ghost btn-sm">همه محصولات ←</a>
        </div>

        <div class="sz-grid sz-grid-4">
            @forelse($products as $ad)
                <x-ad-card :ad="$ad"/>
            @empty
                <div class="empty-state" style="grid-column:1/-1">
                    <span data-icon="search"></span>
                    <h3>هنوز محصول ویژه‌ای ثبت نشده.</h3>
                </div>
            @endforelse
        </div>

        @if($products->hasPages())
            <div class="featured-pager">{{ $products->links() }}</div>
        @endif

        {{-- خدمات ویژه --}}
        <div class="featured-head featured-head--spaced">
            <h2>خدمات ویژه</h2>
            <a href="{{ route('services') }}" class="btn btn-ghost btn-sm">همه خدمات ←</a>
        </div>

        <div class="sz-grid sz-grid-4">
            @forelse($services as $ad)
                <x-ad-card :ad="$ad"/>
            @empty
                <div class="empty-state" style="grid-column:1/-1">
                    <span data-icon="search"></span>
                    <h3>هنوز خدمت ویژه‌ای ثبت نشده.</h3>
                </div>
            @endforelse
        </div>

        @if($services->hasPages())
            <div class="featured-pager">{{ $services->links() }}</div>
        @endif

    </div>
</section>

@endsection
