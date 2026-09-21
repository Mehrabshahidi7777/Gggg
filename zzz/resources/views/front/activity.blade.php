@extends('layouts.app')
@section('title','فعالیت‌های من')
@section('meta_description','امتیازها، نظرها و شماره‌هایی که در سازمت دیده‌اید.')

@section('content')
<div class="container" style="padding-top:32px;padding-bottom:60px;">

    <div class="activity-head">
        <div>
            <h1>فعالیت‌های من</h1>
            <p>امتیازها و نظرهایی که ثبت کرده‌ای، و شماره‌هایی که دیده‌ای.</p>
        </div>
        <a href="{{ route('products') }}" class="btn btn-navy">مشاهده محصولات</a>
    </div>

    <div class="sz-grid sz-grid-3 activity-stats">
        <div class="corner-card stat-card">
            <div class="label">امتیازهای ثبت‌شده</div>
            <div class="value">{{ $stats['ratings'] }}</div>
        </div>
        <div class="corner-card stat-card">
            <div class="label">نظرهای نوشته‌شده</div>
            <div class="value">{{ $stats['comments'] }}</div>
        </div>
        <div class="corner-card stat-card">
            <div class="label">شماره‌های دیده‌شده</div>
            <div class="value">{{ $stats['reveals'] }}</div>
        </div>
    </div>

    {{--
        شماره‌هایی که دیده‌ام

        کاربردی‌ترین بخش این صفحه. کسی که هفته‌ی پیش با سه فروشنده
        تماس گرفته، معمولاً یادش نمی‌ماند کدام بودند.

        شماره اینجا دوباره از سرور گرفته نمی‌شود - همان شماره‌ی آگهی
        است و کاربر قبلاً آن را دیده، پس نمایشش سرنخ تازه‌ای نیست.
    --}}
    <section class="corner-card activity-card">
        <h2>شماره‌هایی که دیده‌ام</h2>

        @forelse($reveals as $reveal)
            <div class="activity-row">
                <div class="activity-row__main">
                    @if($reveal->ad)
                        <a href="{{ route('ad.show', $reveal->ad->slug) }}" class="activity-row__title">
                            {{ $reveal->ad->title }}
                        </a>
                        <span class="activity-row__tag">
                            {{ $reveal->ad->type === 'service' ? 'خدمت' : 'محصول' }}
                        </span>
                    @else
                        <span class="activity-row__title activity-row__title--gone">آگهی حذف شده است</span>
                    @endif
                </div>

                <div class="activity-row__side">
                    @if($reveal->ad?->phone)
                        <a class="activity-phone" dir="ltr"
                           href="tel:{{ $reveal->ad->phone }}">{{ $reveal->ad->phone }}</a>
                    @endif
                    <time class="activity-row__date">{{ $reveal->revealed_on?->format('Y/m/d') }}</time>
                </div>
            </div>
        @empty
            <p class="activity-empty">
                هنوز شماره‌ای ندیده‌ای. وقتی روی «نمایش شماره» یک آگهی بزنی، اینجا ثبت می‌شود
                تا بعداً راحت پیدایش کنی.
            </p>
        @endforelse

        @if($reveals->hasPages())
            <div class="activity-pager">{{ $reveals->links() }}</div>
        @endif
    </section>

    {{--
        امتیازها و نظرها

        هر دو از یک جدول می‌آیند، چون نظر بدون ستاره ثبت نمی‌شود.
        وضعیت نظر (در انتظار / تأیید / رد) اینجا نشان داده می‌شود -
        وگرنه کاربر نظرش را در صفحه‌ی آگهی نمی‌بیند و فکر می‌کند گم
        شده است.
    --}}
    <section class="corner-card activity-card">
        <h2>امتیازها و نظرهای من</h2>

        @forelse($ratings as $rating)
            <div class="activity-row activity-row--stacked">
                <div class="activity-row__main">
                    @if($rating->ad)
                        <a href="{{ route('ad.show', $rating->ad->slug) }}" class="activity-row__title">
                            {{ $rating->ad->title }}
                        </a>
                    @else
                        <span class="activity-row__title activity-row__title--gone">آگهی حذف شده است</span>
                    @endif

                    <span class="activity-stars" role="img"
                          aria-label="{{ $rating->rating }} از ۵ ستاره">
                        @for($i = 1; $i <= 5; $i++)<span
                            class="activity-star {{ $i <= $rating->rating ? 'is-on' : '' }}">★</span>@endfor
                    </span>

                    <span class="activity-row__tag">
                        {{ \App\Models\AdRating::LABELS[$rating->rating] ?? '' }}
                    </span>
                </div>

                @if($rating->comment)
                    <p class="activity-comment">{{ $rating->comment }}</p>

                    @if($rating->comment_status === 'pending')
                        <span class="activity-badge activity-badge--pending">نظر در انتظار تأیید</span>
                    @elseif($rating->comment_status === 'rejected')
                        <span class="activity-badge activity-badge--rejected">
                            نظر تأیید نشد@if($rating->comment_rejection_reason) — {{ $rating->comment_rejection_reason }}@endif
                        </span>
                    @elseif($rating->comment_status === 'approved')
                        <span class="activity-badge activity-badge--approved">نظر منتشر شد</span>
                    @endif
                @endif

                <time class="activity-row__date">{{ $rating->created_at?->format('Y/m/d') }}</time>
            </div>
        @empty
            <p class="activity-empty">
                هنوز به آگهی‌ای امتیاز نداده‌ای. امتیاز تو به بقیه کمک می‌کند ارائه‌دهنده‌ی
                بهتر را پیدا کنند.
            </p>
        @endforelse

        @if($ratings->hasPages())
            <div class="activity-pager">{{ $ratings->links() }}</div>
        @endif
    </section>

    {{--
        سفارش‌های قدیمی فقط برای کسی که واقعاً سفارشی دارد. خرید
        آنلاین خاموش است، پس برای بقیه این لینک یک صفحه‌ی خالی است.
    --}}
    @if($hasOrders)
        <p class="activity-orders-link">
            <a href="{{ route('orders.index') }}">سفارش‌های قبلی من ←</a>
        </p>
    @endif

</div>
@endsection
