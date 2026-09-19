@extends('admin.layouts.app')

@section('content')

<div class="flex justify-between gap-3 flex-wrap items-center">
    <h1 class="text-2xl font-black">{{ $ad->title }}</h1>
    <div class="flex gap-2 flex-wrap">
        <a href="{{ route('admin.ads.edit',$ad) }}" class="btn-primary">ویرایش</a>
        <form method="POST" action="{{ route('admin.ads.destroy',$ad) }}" onsubmit="return confirm('این آگهی، تصاویر آن و رکوردهای وابسته‌اش حذف می‌شود. این کار قابل برگشت نیست. ادامه می‌دهید؟');">
            @csrf
            @method('DELETE')
            <button type="submit" style="background:#b91c1c;color:#fff;border-radius:8px;padding:10px 14px;font-weight:800;">حذف کامل آگهی</button>
        </form>
    </div>
</div>

<div class="grid lg:grid-cols-3 gap-5 mt-5">

    <div class="bg-white rounded-xl p-5 lg:col-span-2 space-y-5">

        <div>
            <p class="text-xs text-gray-400 mb-1">توضیحات</p>
            <p class="whitespace-pre-line">{{ $ad->description ?: '—' }}</p>
        </div>

        <div class="grid sm:grid-cols-2 gap-3 mt-5">
            @forelse ($ad->images as $img)
                <div class="relative">
                    <img
                        class="h-48 w-full object-cover rounded-lg"
                        src="{{ Storage::url($img->path) }}"
                        alt="{{ $ad->title }}"
                    >
                    @if ($img->is_primary)
                        <span class="absolute top-2 right-2 bg-black/70 text-white text-xs rounded px-2 py-1">تصویر اصلی</span>
                    @endif
                </div>
            @empty
                <p class="text-sm text-gray-400">تصویری ثبت نشده است.</p>
            @endforelse
        </div>

        <div class="border-t pt-4">
            <p class="text-sm font-bold text-gray-500 mb-2">
                {{ $ad->type === 'product' ? 'مشخصات محصول' : 'مشخصات خدمت' }}
            </p>

            @if ($ad->type === 'product')
                <div class="grid sm:grid-cols-2 gap-y-2 text-sm">
                    <p>برند: {{ $ad->brand ?: '—' }}</p>
                    <p>مدل: {{ $ad->model ?: '—' }}</p>
                    <p>وضعیت کالا: {{ $ad->condition === 'new' ? 'نو' : ($ad->condition === 'used' ? 'کارکرده' : '—') }}</p>
                    <p dir="ltr" class="text-right">شماره شبا: {{ $ad->card_number ? 'IR'.$ad->card_number : '—' }}</p>
                </div>
            @else
                <div class="grid sm:grid-cols-2 gap-y-2 text-sm">
                    <p>نام ارائه‌دهنده: {{ $ad->full_name ?: '—' }}</p>
                    <p>عنوان خدمت: {{ $ad->service_title ?: '—' }}</p>
                    <p class="sm:col-span-2">
                        وب‌سایت:
                        @if ($ad->website)
                            <a href="{{ $ad->website }}" target="_blank" rel="noopener" class="text-blue-600">{{ $ad->website }}</a>
                        @else
                            —
                        @endif
                    </p>
                </div>
            @endif
        </div>
    </div>

    <div class="bg-white rounded-xl p-5 space-y-3">
        <p>نوع: {{ $ad->type === 'product' ? 'محصول' : 'خدمت' }}</p>

        <p>
            کاربر:
            <a href="{{ route('admin.users.show', $ad->user) }}" class="text-blue-600">{{ $ad->user->name }}</a>
        </p>

        <p>دسته‌بندی: {{ $ad->category->name ?? '—' }}</p>
        <p>استان / شهر: {{ $ad->province->name ?? '—' }} / {{ $ad->city->name ?? '—' }}</p>

        <p>
            وضعیت: {{ $ad->status_text }}
            @if ($ad->status === 'rejected' && $ad->rejection_reason)
                <span class="block text-xs text-gray-400 mt-1">دلیل رد: {{ $ad->rejection_reason }}</span>
            @endif
        </p>

        <p>قیمت: {{ $ad->price_formatted }}</p>
        <p>آدرس: {{ $ad->address ?: '—' }}</p>
        <p dir="ltr" class="text-right">تلفن: {{ $ad->phone ?: '—' }}</p>

        <p>ویژه: {{ $ad->is_featured ? 'بله' : 'خیر' }}</p>

        <p>
            تعلیق: {{ $ad->is_suspended ? 'بله' : 'خیر' }}
            @if ($ad->is_suspended && $ad->suspended_at)
                <span class="block text-xs text-gray-400 mt-1">از {{ $ad->suspended_at->format('Y/m/d H:i') }}</span>
            @endif
        </p>

        <p>بازدید: {{ $ad->views_count }}</p>
        <p>انقضا: {{ optional($ad->expires_at)->format('Y/m/d H:i') ?: '—' }}</p>
        <p class="text-xs text-gray-400">ثبت‌شده: {{ $ad->created_at->format('Y/m/d H:i') }}</p>

        <form method="POST" action="{{ route('admin.ads.approve',$ad) }}" class="mt-5">
            @csrf
            <button class="btn-primary w-full">
                تأیید
            </button>
        </form>

        <form method="POST" action="{{ route('admin.ads.reject',$ad) }}" class="mt-2">
            @csrf

            <input
                name="rejection_reason"
                class="w-full rounded-lg border mb-2"
                placeholder="دلیل رد (اختیاری)"
            >

            <button class="bg-red-600 text-white rounded-lg px-4 py-2 w-full">
                رد آگهی
            </button>
        </form>

        <form method="POST" action="{{ route('admin.ads.feature',$ad) }}" class="mt-2">
            @csrf

            <button class="bg-orange-500 text-white rounded-lg px-4 py-2 w-full">
                {{ $ad->is_featured ? 'حذف ویژه' : 'ویژه کردن' }}
            </button>
        </form>
    </div>

</div>

@endsection
