@props([
    'ad',
    /*
    | compact = حالت کارت (ستاره‌های کوچک، برچسب فقط هنگام انتخاب)
    | full    = حالت صفحه‌ی آگهی (بزرگ، با راهنمای کامل حدها)
    */
    'size' => 'compact',
])

@php
    $average = $ad->rating_average;
    $count = $ad->rating_count;

    $labels = \App\Models\AdRating::LABELS;

    $isOwner = auth()->check() && auth()->id() === $ad->user_id;

    /*
    | امتیازی که همین کاربر قبلاً داده (اگر داده باشد) تا ستاره‌ها از
    | ابتدا پر نشان داده شوند و بداند می‌تواند تغییرش دهد.
    | myRating در کوئری‌های فهرست با scopeWithRatingSummary از قبل
    | eager load شده است، پس اینجا کوئری اضافه‌ای زده نمی‌شود.
    */
    $mine = auth()->check() ? $ad->myRating?->rating : null;

    $widgetId = 'rate-' . $ad->id . '-' . $size;
@endphp

<div class="star-rate star-rate--{{ $size }}" id="{{ $widgetId }}" data-ad="{{ $ad->id }}">

    {{-- خلاصه‌ی وضعیت فعلی --}}
    <div class="star-rate__summary">
        @if($count > 0)
            <span class="star-rate__avg">{{ number_format($average, 1) }}</span>
            <span class="star-rate__count">از {{ $count }} امتیاز</span>
        @else
            <span class="star-rate__count">هنوز امتیازی ثبت نشده</span>
        @endif
    </div>

    @if($isOwner)

        {{-- صاحب آگهی حق امتیازدهی ندارد؛ فقط میانگین را می‌بیند. --}}
        <div class="star-rate__stars star-rate__stars--readonly" aria-label="میانگین امتیاز {{ $count ? number_format($average,1) : 0 }} از ۵">
            @for($i = 1; $i <= 5; $i++)
                <span class="star {{ $average !== null && $i <= round($average) ? 'is-on' : '' }}">★</span>
            @endfor
        </div>
        <p class="star-rate__hint">امتیاز آگهی خودتان را نمی‌توانید ثبت کنید.</p>

    @elseif(auth()->check())

        <form
            method="POST"
            action="{{ route('ad.rate', $ad) }}"
            class="star-rate__form"
            data-star-form
        >
            @csrf

            {{--
                صفحه RTL است. برای اینکه ستاره‌ی «افتضاح» سمت چپ و
                ستاره‌ی «بسیار عالی» سمت راست بیفتد، این ظرف عمداً
                direction:ltr می‌گیرد و ستاره‌ها از ۱ تا ۵ رندر می‌شوند.
            --}}
            <div class="star-rate__stars" role="radiogroup" aria-label="امتیاز شما به این آگهی">
                @for($i = 1; $i <= 5; $i++)
                    <label class="star-rate__star" title="{{ $labels[$i] }}">
                        <input
                            type="radio"
                            name="rating"
                            value="{{ $i }}"
                            {{ $mine === $i ? 'checked' : '' }}
                            aria-label="{{ $i }} ستاره - {{ $labels[$i] }}"
                        >
                        <span class="star" aria-hidden="true">★</span>
                    </label>
                @endfor
            </div>

            {{-- راهنمای حدها: چپ = بدترین، راست = بهترین --}}
            <div class="star-rate__scale" aria-hidden="true">
                <span>{{ $labels[1] }}</span>
                <span>{{ $labels[5] }}</span>
            </div>

            <p class="star-rate__live" data-star-live>
                @if($mine)
                    امتیاز شما: {{ $labels[$mine] }}
                @else
                    برای امتیازدادن یک ستاره را انتخاب کنید
                @endif
            </p>

            @if($size === 'full')
                <div class="star-rate__legend">
                    @foreach($labels as $value => $label)
                        <span><b>{{ $value }}</b> {{ $label }}</span>
                    @endforeach
                </div>
            @endif

            {{-- اگر جاوااسکریپت غیرفعال باشد، فرم به‌صورت معمولی ارسال می‌شود. --}}
            <noscript>
                <button type="submit" class="btn btn-navy btn-sm">ثبت امتیاز</button>
            </noscript>
        </form>

    @else

        {{-- مهمان: ستاره‌ها دیده می‌شوند ولی کلیک به صفحه‌ی ورود می‌برد. --}}
        <a href="{{ route('login') }}" class="star-rate__stars star-rate__stars--guest" title="برای ثبت امتیاز وارد شوید">
            @for($i = 1; $i <= 5; $i++)
                <span class="star {{ $average !== null && $i <= round($average) ? 'is-on' : '' }}">★</span>
            @endfor
        </a>
        <p class="star-rate__hint">برای ثبت امتیاز <a href="{{ route('login') }}">وارد شوید</a>.</p>

    @endif

</div>
