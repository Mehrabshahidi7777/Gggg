@extends('layouts.app')

@section('title','پنل ارائه محصولات')

@section('content')

<div class="container" style="padding-top:32px;padding-bottom:60px;max-width:900px;">

    <div style="display:flex;justify-content:space-between;align-items:center;gap:16px;flex-wrap:wrap;margin-bottom:28px;">
        <div>
            <h1 style="margin-bottom:6px;">پنل ارائه محصولات</h1>
            <p style="color:var(--color-steel);">مدیریت اشتراک و آگهی‌های محصول شما</p>
        </div>

        <a class="btn btn-navy" href="{{ route('ad.create') }}">افزودن محصول</a>
    </div>

    {{-- Statistics --}}

    <div class="sz-grid sz-grid-4" style="margin-bottom:24px;">

        <div class="corner-card stat-card">
            <div class="label">پلنی که آخرین بار ثبت شده </div>
            <div class="value" style="font-size:1.1rem;">{{ $subscription?->plan?->title ?? 'ندارید' }}</div>
        </div>

        <div class="corner-card stat-card">
            <div class="label">محصولات ثبت‌شده</div>
            <div class="value">{{ $productCount }}</div>
        </div>

        {{--
            «سرنخ» یعنی دفعاتی که یک بازدیدکننده روی «نمایش شماره»
            کلیک کرده - نه صرفاً بازدید صفحه. این عددی است که نشان
            می‌دهد اشتراک واقعاً مشتری آورده یا نه. بازدیدکننده‌ی
            تکراری در یک روز فقط یک بار شمرده می‌شود و بازدید خودِ
            صاحب آگهی اصلاً شمرده نمی‌شود.
        --}}
        <div class="corner-card stat-card">
            <div class="label">تماس‌های این ماه</div>
            <div class="value">{{ number_format($leadsThisMonth) }}</div>
            <div style="font-size:.72rem;color:var(--color-steel-light);margin-top:4px;">
                مجموع از ابتدا: {{ number_format($leadsTotal) }}
            </div>
        </div>

        <div class="corner-card stat-card">
            <div class="label">وضعیت اشتراک</div>
            <div class="value" style="font-size:1.1rem;">
                @if(!$subscription)
                    بدون اشتراک
                @elseif($isCurrentlyActive)
                    فعال
                @elseif($subscription->status === 'expired')
                    منقضی شده
                @elseif($subscription->status === 'suspended')
                    در حالت تعلیق
                @elseif($subscription->status === 'pending')
                    در انتظار پرداخت
                @elseif($subscription->status === 'cancelled')
                    لغو شده
                @else
                    نامشخص
                @endif
            </div>
        </div>

    </div>

    {{-- Subscription information --}}

    @if($subscription)

        <div class="corner-card" style="padding:24px;margin-bottom:24px;">

            <div class="sz-grid sz-grid-4">

                <div>
                    <div style="font-size:0.85rem;color:var(--color-steel-light);">شروع اشتراک</div>
                    <div style="font-weight:700;margin-top:4px;">{{ $chainStart?->format('Y/m/d H:i') ?? '—' }}</div>
                </div>

                <div>
                    <div style="font-size:0.85rem;color:var(--color-steel-light);">پایان اشتراک</div>
                    <div style="font-weight:700;margin-top:4px;">{{ $chainEnd?->format('Y/m/d H:i') ?? '—' }}</div>
                </div>

                <div>
                    <div style="font-size:0.85rem;color:var(--color-steel-light);">وضعیت</div>
                    <div style="font-weight:700;margin-top:4px;">
                        @if($isCurrentlyActive)
                            فعال
                        @elseif($subscription->status === 'expired')
                            منقضی شده
                        @elseif($subscription->status === 'suspended')
                            تعلیق
                        @elseif($subscription->status === 'pending')
                            در انتظار پرداخت
                        @elseif($subscription->status === 'cancelled')
                            لغو شده
                        @else
                            نامشخص
                        @endif
                    </div>
                </div>

                <div>
                    <div style="font-size:0.85rem;color:var(--color-steel-light);">پایان مهلت نگهداری</div>
                    <div style="font-weight:700;margin-top:4px;">۶ ماه پس از تعلیق محصولات</div>
                </div>

            </div>

            @if($isCurrentlyActive)

                <div class="bg-green-50 text-green-800 p-4 rounded-xl mt-5 text-sm leading-7">
                    اشتراک شما فعال است و محصولات شما در سایت قابل نمایش هستند.
                </div>

                <a href="{{ route('product.plans') }}" class="btn btn-navy" style="margin-top:16px;display:inline-block;">
                    تمدید اشتراک
                </a>

            @else

                <div class="bg-amber-50 text-amber-800 p-4 rounded-xl mt-5 text-sm leading-7">
                    اشتراک شما به پایان رسیده است.
                    <br>
                    محصولات شما در حالت تعلیق قرار گرفته‌اند و در سایت نمایش داده نمی‌شوند.
                    <br>
                    برای فعال شدن دوباره، ابتدا باید اشتراک جدید خود را پرداخت کنید.
                    <br>
                    تا پایان مهلت نگهداری، محصولات شما در پایگاه داده باقی می‌مانند.
                    پس از پایان این مهلت، محصولات تعلیق‌شده به صورت خودکار و دائمی حذف خواهند شد.
                </div>

                <a href="{{ route('product.plans') }}" class="btn btn-navy" style="margin-top:16px;display:inline-block;">
                    تمدید و پرداخت اشتراک
                </a>

            @endif

        </div>

    @endif

    {{-- Products --}}

    @if($products->count())

        <div class="corner-card" style="padding:24px;">

            <h2 style="font-size:1.15rem;">محصولات شما</h2>
            <p style="font-size:0.85rem;color:var(--color-steel);margin-top:6px;">
                محصولات تعلیق‌شده تا زمان پرداخت اشتراک جدید در سایت نمایش داده نمی‌شوند.
            </p>

            <div class="sz-grid sz-grid-2" style="margin-top:20px;">

                @foreach($products as $product)

                    <div class="corner-card" style="padding:16px;">

                        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;">

                            <div>
                                <div style="font-weight:800;">{{ $product->title }}</div>
                                <div style="font-size:0.85rem;color:var(--color-steel-light);margin-top:6px;">{{ $product->status_text }}</div>

                                {{--
                                    ویرایش همیشه در دسترس است، ولی اگر
                                    درخواست بررسی‌نشده‌ای در صف باشد
                                    وضعیتش همین‌جا اعلام می‌شود تا کاربر
                                    فکر نکند تغییرش گم شده.
                                --}}
                                @php $pending = $product->edits()->pending()->exists(); @endphp

                                <div style="margin-top:10px;display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                                    <a class="btn btn-ghost btn-sm" href="{{ route('ad.edit', $product) }}">ویرایش اطلاعات</a>

                                    @if($pending)
                                        <span style="font-size:.75rem;color:var(--color-amber);font-weight:800;">
                                            ویرایش در انتظار تأیید مدیر
                                        </span>
                                    @endif
                                </div>
                            </div>

                            @if($product->is_suspended)
                                <span class="text-xs bg-amber-100 text-amber-800 rounded-lg px-3 py-2 whitespace-nowrap">تعلیق شده</span>
                            @elseif($product->status === 'approved')
                                <span class="text-xs bg-green-100 text-green-800 rounded-lg px-3 py-2 whitespace-nowrap">فعال</span>
                            @endif

                        </div>

                        @if($product->is_suspended)
                            <div class="bg-amber-50 text-amber-800 rounded-xl p-3 mt-4 text-sm leading-6">
                                این محصول به دلیل پایان اشتراک از سایت خارج شده است.
                                برای نمایش دوباره آن، اشتراک خود را تمدید کنید.
                            </div>
                        @endif

                        @if($product->is_suspended && $product->suspended_at)
                            <div class="text-xs text-amber-700 mt-3">
                                شروع تعلیق: {{ $product->suspended_at->format('Y/m/d H:i') }}
                            </div>
                        @endif

                    </div>

                @endforeach

            </div>

        </div>

    @endif

</div>

@endsection
