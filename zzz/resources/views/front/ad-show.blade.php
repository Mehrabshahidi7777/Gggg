@extends('layouts.app')

@section('title', $ad->title . ' | سازمت')

{{--
    توضیح متا اختصاصی هر آگهی.

    قبلاً همه‌ی صفحه‌های سایت یک توضیح یکسان داشتند. گوگل توضیح
    تکراری را نشانه‌ی صفحه‌ی کم‌ارزش می‌داند و معمولاً خودش متنی از
    صفحه برمی‌دارد که کنترلی رویش نداریم. این متن از دسته‌بندی،
    شهر و توضیح خودِ آگهی ساخته می‌شود و به ۱۶۰ نویسه محدود است -
    طولانی‌تر از آن در نتایج گوگل بریده می‌شود.
--}}
@section('meta_description', \Illuminate\Support\Str::limit(
    $ad->category->name . ' در ' . $ad->city->name . '، ' . $ad->province->name . '. '
    . ($ad->description ?: $ad->title),
    155
))

@push('head')
{{--
    داده‌ی ساخت‌یافته برای نتایج گوگل.

    مهم‌ترین بخشش aggregateRating است: وقتی آگهی امتیاز داشته باشد،
    گوگل می‌تواند ستاره‌ها را مستقیماً کنار نتیجه نشان دهد و همین
    نرخ کلیک را به‌شکل محسوسی بالا می‌برد.

    aggregateRating فقط وقتی اضافه می‌شود که واقعاً امتیازی ثبت شده
    باشد؛ فرستادن ratingValue خالی یا صفر باعث خطای Rich Result در
    سرچ کنسول می‌شود.
--}}
@php
    /*
    | کلیدهای null قبل از تولید JSON حذف می‌شوند. فرستادن مقدار null
    | به گوگل باعث خطای «Invalid value» در گزارش Rich Result می‌شود.
    */
    $schema = array_filter([
        '@context' => 'https://schema.org',
        '@type' => $ad->type === 'service' ? 'Service' : 'Product',
        'name' => $ad->title,
        'description' => \Illuminate\Support\Str::limit($ad->description ?: $ad->title, 400),
        'url' => route('ad.show', $ad->slug),
        'category' => $ad->category->name,
        'image' => $ad->images->map(fn ($i) => asset('storage/' . $i->path))->values()->all(),
        'areaServed' => $ad->province->name . '، ' . $ad->city->name,

        'brand' => $ad->brand
            ? ['@type' => 'Brand', 'name' => $ad->brand]
            : null,

        /*
        | قیمت‌ها در این سایت به «تومان» ذخیره می‌شوند، اما schema.org
        | کد ارز استاندارد ISO 4217 می‌خواهد و کد رسمی ایران IRR
        | (ریال) است - کدی به نام IRT وجود ندارد و گوگل آن را
        | نمی‌پذیرد. پس مقدار باید به ریال تبدیل شود، وگرنه قیمتی که
        | در نتایج گوگل نمایش داده می‌شود یک‌دهمِ قیمت واقعی است.
        */
        'offers' => $ad->price
            ? [
                '@type' => 'Offer',
                'price' => (string) ((int) $ad->price * 10),
                'priceCurrency' => 'IRR',
                'availability' => 'https://schema.org/InStock',
                'url' => route('ad.show', $ad->slug),
            ]
            : null,

        'aggregateRating' => $ad->rating_count
            ? [
                '@type' => 'AggregateRating',
                'ratingValue' => (string) $ad->rating_average,
                'reviewCount' => $ad->rating_count,
                'bestRating' => '5',
                'worstRating' => '1',
            ]
            : null,

    ], fn ($v) => $v !== null && $v !== [] && $v !== '');
@endphp
<script type="application/ld+json">
{!! json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}
</script>
@endpush

@section('content')

<div class="container" style="padding-top:28px;">
    <div class="breadcrumb">
        <a href="{{ route('home') }}">خانه</a>
        <span aria-hidden="true">/</span>
        <a href="{{ $ad->type === 'product' ? route('products') : route('services') }}">
            {{ $ad->type === 'product' ? 'محصولات' : 'خدمات' }}
        </a>
        <span aria-hidden="true">/</span>
        <span>{{ $ad->title }}</span>
    </div>
</div>

<section class="section" style="padding-top:0;">
    <div class="container detail-grid">

        <div>

            @if($ad->images->count())
                <div class="detail-gallery-main" id="detailMainImg"><img src="{{ Storage::url($ad->images->first()->path) }}" alt="{{ $ad->title }}" loading="eager"></div>

                @if($ad->images->count() > 1)
                    <div class="detail-thumbs">
                        @foreach($ad->images as $img)
                            <div
                                class="{{ $loop->first ? 'is-active' : '' }}"
                                style="background-image:url('{{ Storage::url($img->path) }}');background-size:cover;background-position:center;"
                                onclick="document.getElementById('detailMainImg').style.backgroundImage=`url({{ Storage::url($img->path) }})`;document.querySelectorAll('.detail-thumbs div').forEach(d=>d.classList.remove('is-active'));this.classList.add('is-active');"
                            ></div>
                        @endforeach
                    </div>
                @endif
            @else
                <div class="detail-gallery-main">
                    <span data-icon="box"></span>
                </div>
            @endif

            <h1 style="margin-top:24px;">{{ $ad->title }}</h1>

            <div style="color:var(--color-blueprint);font-size:1.5rem;font-weight:900;margin-top:10px;">
                {{ $ad->price_formatted }}
            </div>

            @if($ad->rating_count)
                <div style="margin-top:10px;color:var(--color-orange);font-weight:800;">
                    {{ str_repeat('★', (int) round($ad->rating_average)) }}{{ str_repeat('☆', 5 - (int) round($ad->rating_average)) }}
                    {{ number_format($ad->rating_average, 1) }} از {{ $ad->rating_count }} امتیاز
                </div>
            @endif

            <div class="detail-tags">
                <span class="chip">{{ $ad->category->name }}</span>
                <span class="chip">{{ $ad->province->name }}، {{ $ad->city->name }}</span>
            </div>

            <p style="white-space:pre-line;line-height:1.9;color:var(--color-steel);margin-top:10px;">
                {{ $ad->description }}
            </p>

            <div class="spec-list">
                <div class="spec-row"><span>دسته‌بندی</span><span>{{ $ad->category->name }}</span></div>
                <div class="spec-row"><span>موقعیت</span><span>{{ $ad->province->name }}، {{ $ad->city->name }}</span></div>
                <div class="spec-row"><span>بازدید</span><span>{{ $ad->views_count }}</span></div>
            </div>

            {{--
                خرید آنلاین برداشته شده و آگهی محصول دقیقاً مثل آگهی
                خدمت کار می‌کند: کاربر با ارائه‌دهنده تماس می‌گیرد.
                اگر روزی config('marketplace.online_checkout') روشن شود،
                دکمه‌ی سبد خرید دوباره برمی‌گردد.
            --}}
            @if($ad->type === 'product' && config('marketplace.online_checkout'))
                <form method="POST" action="{{ route('cart.add', $ad) }}" style="display:flex;gap:12px;align-items:center;margin-top:28px;">
                    @csrf
                    <input type="number" name="quantity" value="1" min="1" max="99" style="width:90px;padding:12px 14px;border:1.5px solid var(--color-line);border-radius:var(--radius-sm);">
                    <button class="btn btn-navy" type="submit">افزودن به سبد خرید</button>
                </form>
                <p style="font-size:0.8rem;color:var(--color-steel-light);margin-top:10px;">
                    برای خرید، بعد از افزودن به سبد خرید شماره تماس و آدرس تحویل را وارد می‌کنید.
                </p>
            @endif

            {{-- امتیازدهی ۵ ستاره‌ای --}}
            <div style="margin-top:32px;">
                <h2 style="font-size:1.05rem;margin-bottom:12px;">به این آگهی امتیاز بدهید</h2>
                <x-star-rating :ad="$ad" size="full"/>
            </div>

        </div>

        <aside class="detail-side">

            <span class="listing-badge" style="position:static;display:inline-block;margin-bottom:16px;">{{ $ad->category->name }}</span>

            <h2 style="font-size:1.1rem;margin-bottom:14px;">اطلاعات ارائه‌دهنده</h2>

            <div class="provider-row">
                <div class="provider-avatar">{{ mb_substr($ad->user->username ?: $ad->user->name, 0, 1) }}</div>
                <div><a href="{{ route('seller.profile',$ad->user) }}" style="font-weight:800;color:var(--color-blueprint);">{{ $ad->user->username ?: $ad->user->name }}</a><div style="font-size:.8rem;color:var(--color-steel-light);margin-top:4px;">برای اطلاعات بیشتر روی نام ارائه‌دهنده بزنید</div></div>
            </div>

            {{--
                اطلاعات تماس برای هر دو نوع آگهی (محصول و خدمت) و برای
                همه‌ی بازدیدکننده‌ها نمایش داده می‌شود. قبلاً فقط خدمت
                شماره تلفن را نشان می‌داد و آدرس اصلاً نمایش داده
                نمی‌شد، در حالی که هر دو فیلد موقع ثبت آگهی الزامی‌اند.
            --}}
            <div class="contact-box">

                @if($ad->full_name)
                    <div class="contact-row">
                        <span data-icon="users"></span>
                        <div>
                            <span class="contact-row__label">نام ارائه‌دهنده</span>
                            <span class="contact-row__value">{{ $ad->full_name }}</span>
                        </div>
                    </div>
                @endif

                {{--
                    شماره عمداً داخل HTML چاپ نمی‌شود. با کلیک روی دکمه
                    از مسیر ad.contact گرفته می‌شود تا ربات‌ها نتوانند با
                    یک خزش ساده کل شماره‌های سایت را جمع کنند، و در عین
                    حال هر نمایش برای آمار پنل ارائه‌دهنده ثبت شود.
                --}}
                @if($ad->phone)
                    <div class="contact-row" data-contact-box data-url="{{ route('ad.contact', $ad) }}">
                        <span data-icon="phone"></span>
                        <div style="flex:1;">
                            <span class="contact-row__label">شماره تماس</span>

                            <button type="button" class="contact-reveal" data-contact-reveal>
                                نمایش شماره
                            </button>

                            <a
                                class="contact-row__value contact-row__value--ltr contact-reveal__value"
                                data-contact-value
                                href="#"
                                hidden
                            ></a>
                        </div>
                    </div>
                @endif

                @if($ad->address)
                    <div class="contact-row">
                        <span data-icon="map"></span>
                        <div>
                            <span class="contact-row__label">آدرس</span>
                            <span class="contact-row__value">{{ $ad->address }}</span>
                        </div>
                    </div>
                @endif

            </div>

            @if($ad->phone)
                <button type="button" class="btn btn-navy btn-block" style="margin-bottom:10px;" data-contact-reveal>
                    تماس با ارائه‌دهنده
                </button>
            @endif

            @if($ad->website)
                <a class="btn btn-outline-navy btn-block" target="_blank" rel="noopener" href="{{ $ad->website }}">
                    وب‌سایت
                </a>
            @endif

        </aside>

    </div>
</section>

{{--
    نظرهای کاربران.

    فقط نظرهایی که مدیر تأیید کرده نمایش داده می‌شوند — رابطه‌ی
    approvedComments خودش این فیلتر را دارد، پس نظر تأییدنشده حتی
    اگر اشتباهی هم صدا زده شود اینجا دیده نمی‌شود.
--}}
@if($ad->approvedComments->count())
<section class="section section--tight">
    <div class="container">
        <div class="section-head">
            <h2>نظر کاربران درباره این آگهی</h2>
        </div>

        <div class="sz-grid sz-grid-2">
            @foreach($ad->approvedComments->take(6) as $comment)
                <div class="corner-card" style="padding:18px;">
                    <div style="display:flex;justify-content:space-between;gap:8px;align-items:center;">
                        <strong>{{ $comment->user?->username ?: ($comment->user?->name ?? 'کاربر') }}</strong>
                        <span style="color:var(--color-orange);letter-spacing:2px;" title="{{ \App\Models\AdRating::labelFor($comment->rating) }}">
                            {{ str_repeat('★', $comment->rating) }}{{ str_repeat('☆', 5 - $comment->rating) }}
                        </span>
                    </div>
                    <p style="color:var(--color-steel);line-height:1.8;margin-top:8px;white-space:pre-line;">{{ $comment->comment }}</p>
                    <div style="font-size:.72rem;color:var(--color-steel-light);margin-top:8px;">
                        {{ $comment->created_at->format('Y/m/d') }}
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- نظرهای تأییدشده‌ی خریدهای آنلاین قبلی (سیستم قدیمی) --}}
@if($ad->reviews->count())
<section class="section section--tight">
    <div class="container">
        <div class="section-head"><h2>نظر خریداران پیشین</h2></div>
        <div class="sz-grid sz-grid-2">
            @foreach($ad->reviews->take(4) as $review)
                <div class="corner-card" style="padding:18px;"><div style="display:flex;justify-content:space-between;gap:8px;"><strong>{{ $review->buyer?->username ?: ($review->buyer?->name ?? 'مشتری') }}</strong><span style="color:var(--color-orange);letter-spacing:2px;">{{ str_repeat('★',$review->rating) }}</span></div>@if($review->comment)<p style="color:var(--color-steel);line-height:1.8;margin-top:8px;">{{ $review->comment }}</p>@endif</div>
            @endforeach
        </div>
    </div>
</section>
@endif

@if($similarAds->count())
    <section class="section section--tint">
        <div class="container">

            <div class="section-head">
                <h2>آگهی‌های مشابه</h2>
            </div>

            <div class="sz-grid sz-grid-4">
                @foreach($similarAds as $similar)
                    <x-ad-card :ad="$similar"/>
                @endforeach
            </div>

        </div>
    </section>
@endif

@endsection
