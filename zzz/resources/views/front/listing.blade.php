@extends('layouts.app')
@section('title', $type==='product' ? 'محصولات ساختمانی' : 'خدمات ساختمانی')
@section('content')

<div class="container" style="padding-top:32px;padding-bottom:60px;">

    <h1 style="margin-bottom:24px;">{{ $type==='product' ? 'محصولات ساختمانی' : 'خدمات ساختمانی' }}</h1>

    <form method="GET" class="filter-bar">

        <div class="search-field">
            <input
                type="text"
                name="search"
                value="{{ request('search') }}"
                placeholder="جستجو..."
                aria-label="جستجو"
            >
        </div>

        <button type="button" class="filter-toggle-mobile" id="filterToggleBtn">
            <span data-icon="filter"></span>
            <span>فیلترها</span>
        </button>

        <div class="filter-collapsible" id="filterCollapsible">

        <div class="filter-bar-grid">

            <select name="category" aria-label="دسته‌بندی">
                <option value="">همه دسته‌بندی‌ها</option>
                @foreach($categories as $c)
                    <option value="{{ $c->id }}" @selected(request('category')==$c->id)>{{ $c->name }}</option>
                @endforeach
            </select>

            <select name="province" id="province" aria-label="استان">
                <option value="">همه استان‌ها</option>
                @foreach($provinces as $p)
                    <option value="{{ $p->id }}" @selected(request('province')==$p->id)>{{ $p->name }}</option>
                @endforeach
            </select>

            <select name="city" id="city" aria-label="شهر">
                <option value="">همه شهرها</option>
                @if(request('province'))
                    @foreach($provinces->firstWhere('id',(int)request('province'))?->cities ?? [] as $c)
                        <option value="{{ $c->id }}" @selected(request('city')==$c->id)>{{ $c->name }}</option>
                    @endforeach
                @endif
            </select>

            <select name="sort" aria-label="مرتب‌سازی">
                <option value="latest" @selected(request('sort','latest')==='latest')>جدیدترین</option>
                <option value="popular" @selected(request('sort')==='popular')>محبوب‌ترین</option>
                <option value="price_low" @selected(request('sort')==='price_low')>ارزان‌ترین</option>
                <option value="price_high" @selected(request('sort')==='price_high')>گران‌ترین</option>
            </select>

            <input type="number" name="min_price" value="{{ request('min_price') }}" placeholder="حداقل قیمت">
            <input type="number" name="max_price" value="{{ request('max_price') }}" placeholder="حداکثر قیمت">

        </div>

        <button type="submit" class="btn btn-navy">اعمال فیلتر</button>

        </div>

    </form>

    <div class="results-meta">
        <span>{{ $ads->total() }} نتیجه</span>
    </div>

    <div class="sz-grid sz-grid-4">
        @forelse($ads as $ad)
            <x-ad-card :ad="$ad"/>
        @empty
            <div class="empty-state" style="grid-column:1/-1">
                <span data-icon="search"></span>
                <h3>موردی پیدا نشد.</h3>
            </div>
        @endforelse
    </div>

    <div style="margin-top:32px;">
        {{ $ads->links() }}
    </div>

</div>

@push('scripts')
<script>
document.getElementById('province')?.addEventListener('change', async e => {
    const city = document.getElementById('city');
    city.innerHTML = '<option>در حال بارگذاری...</option>';
    if (!e.target.value) {
        city.innerHTML = '<option value="">همه شهرها</option>';
        return;
    }
    const res = await fetch('{{ url('/api/cities') }}/' + e.target.value);
    const data = await res.json();
    city.innerHTML = '<option value="">همه شهرها</option>';
    data.forEach(c => city.insertAdjacentHTML('beforeend', `<option value="${c.id}">${c.name}</option>`));
});

document.getElementById('filterToggleBtn')?.addEventListener('click', () => {
    document.getElementById('filterCollapsible')?.classList.toggle('is-open');
});
</script>
@endpush

@endsection
