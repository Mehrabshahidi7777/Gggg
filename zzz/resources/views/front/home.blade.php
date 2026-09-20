@extends('layouts.app')

@section('title', 'سازمت | بازار آنلاین صنعت ساختمان')

@section('content')

{{-- ===================== HERO ===================== --}}
<section class="hero hero--shop">
    <div class="container">

        <div class="hero-shop-head">
            <div class="eyebrow">بازار آنلاین صنعت ساختمان</div>
            <h1>هر چیزی برای ساختن، یک‌جا</h1>
            <p class="lede">محصولات، خدمات و متخصصان صنعت ساختمان را پیدا کن.</p>
        </div>

        <form class="hero-search" action="{{ route('search') }}" method="GET">
            {{--
                این <label> است، نه <div>: تا زدن روی هر جای این ناحیه -
                آیکون ذره‌بین هم - فیلد را فوکوس کند.

                قبلاً آیکون فقط یک تصویر بود و زدن رویش هیچ کاری نمی‌کرد،
                در حالی که کاربر طبیعتاً فکر می‌کند باید همان را بزند.
            --}}
            <label class="hero-search-field" for="main-search">
                <span data-icon="search"></span>
                <input
                    id="main-search"
                    type="search"
                    name="search"
                    value="{{ request('search') }}"
                    placeholder="مثلاً سیمان، آجر، برق‌کشی، طراحی داخلی..."
                    autocomplete="off"
                    aria-label="جستجو در محصولات و خدمات"
                    required
                >
            </label>
            <button type="submit" class="btn btn-primary">جستجو</button>
        </form>

        <div style="display:flex;justify-content:center;gap:16px;flex-wrap:wrap;margin-top:22px;margin-bottom:8px;">
            <a href="{{ route('products') }}" class="btn btn-navy" style="min-width:160px;">محصولات</a>
            <a href="{{ route('services') }}" class="btn btn-outline-navy" style="min-width:160px;">خدمات</a>
        </div>

    </div>
</section>


{{-- ===================== CATEGORIES ===================== --}}
<section class="section--tight" style="padding-top:0">
    <div class="container">

        <h2 style="font-size:1.4rem;margin-bottom:20px;">دسته‌بندی‌ها</h2>

        <div class="cat-strip">

            @foreach($categories as $category)

                <a
                    href="{{ $category->type === 'product'
                        ? route('products', ['category' => $category->id])
                        : route('services', ['category' => $category->id])
                    }}"
                    class="cat-pill"
                >
                    <span data-icon="grid"></span>
                    <span>{{ $category->name }} — {{ $category->ads_count }} آگهی</span>
                </a>

            @endforeach

            <a href="{{ route('categories.index') }}" class="cat-pill cat-pill--viewall">
                <span data-icon="grid"></span>
                <span>مشاهده همه</span>
            </a>

        </div>

    </div>
</section>


{{-- ===================== FEATURED PRODUCTS ===================== --}}
<section class="section section--tight section--tint">
    <div class="container">

        <div class="section-head" style="display:flex;justify-content:space-between;align-items:flex-end;flex-wrap:wrap;gap:16px;max-width:none;margin-bottom:32px;">
            <div>
                <h2 style="margin-bottom:0">محصولات ویژه</h2>
            </div>
            <a href="{{ route('products') }}" class="btn btn-ghost btn-sm">همه محصولات ←</a>
        </div>

        <div class="sz-grid sz-grid-4">

            @forelse($featuredProducts as $ad)
                <x-ad-card :ad="$ad"/>
            @empty
                <div class="empty-state" style="grid-column:1/-1">
                    <span data-icon="search"></span>
                    <h3>هنوز محصول ویژه‌ای ثبت نشده.</h3>
                </div>
            @endforelse

        </div>

    </div>
</section>


{{-- ===================== FEATURED SERVICES ===================== --}}
<section class="section section--tight">
    <div class="container">

        <div class="section-head" style="display:flex;justify-content:space-between;align-items:flex-end;flex-wrap:wrap;gap:16px;max-width:none;margin-bottom:32px;">
            <div>
                <h2 style="margin-bottom:0">خدمات ویژه</h2>
            </div>
            <a href="{{ route('services') }}" class="btn btn-ghost btn-sm">همه خدمات ←</a>
        </div>

        <div class="sz-grid sz-grid-4">

            @forelse($featuredServices as $ad)
                <x-ad-card :ad="$ad"/>
            @empty
                <div class="empty-state" style="grid-column:1/-1">
                    <span data-icon="search"></span>
                    <h3>هنوز خدمت ویژه‌ای ثبت نشده.</h3>
                </div>
            @endforelse

        </div>

    </div>
</section>


@endsection
