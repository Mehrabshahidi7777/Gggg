@extends('layouts.app')
@section('title', 'همه دسته‌بندی‌ها')
@section('content')

<div class="container" style="padding-top:32px;padding-bottom:60px;">

    <h1 style="margin-bottom:8px;">همه دسته‌بندی‌ها</h1>
    <p style="color:var(--color-steel);margin-bottom:36px;">هر دسته‌بندی که توی سازمت ثبت شده رو از اینجا پیدا کن.</p>

    {{-- محصولات --}}
    <h2 style="font-size:1.15rem;margin-bottom:18px;">دسته‌بندی‌های محصولات</h2>

    <div class="sz-grid sz-grid-4" style="margin-bottom:44px;">
        @forelse($productCategories as $category)
            <a href="{{ route('products', ['category' => $category->id]) }}" class="corner-card" style="padding:20px;text-align:center;">
                <span data-icon="grid" class="dir-cat-icon"></span>
                <h3 style="font-size:0.95rem;margin-bottom:4px;">{{ $category->name }}</h3>
                <p style="font-size:0.8rem;color:var(--color-steel-light);">{{ $category->ads_count }} آگهی</p>
            </a>
        @empty
            <p style="color:var(--color-steel);">هنوز دسته‌بندی محصولی ثبت نشده.</p>
        @endforelse
    </div>

    {{-- خدمات --}}
    <h2 style="font-size:1.15rem;margin-bottom:18px;">دسته‌بندی‌های خدمات</h2>

    <div class="sz-grid sz-grid-4">
        @forelse($serviceCategories as $category)
            <a href="{{ route('services', ['category' => $category->id]) }}" class="corner-card" style="padding:20px;text-align:center;">
                <span data-icon="grid" class="dir-cat-icon"></span>
                <h3 style="font-size:0.95rem;margin-bottom:4px;">{{ $category->name }}</h3>
                <p style="font-size:0.8rem;color:var(--color-steel-light);">{{ $category->ads_count }} آگهی</p>
            </a>
        @empty
            <p style="color:var(--color-steel);">هنوز دسته‌بندی خدمتی ثبت نشده.</p>
        @endforelse
    </div>

</div>

@endsection
