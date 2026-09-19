@extends('admin.layouts.app')

@section('content')

<div class="flex justify-between items-center flex-wrap gap-3">
    <h1 class="text-2xl font-black">ویرایش آگهی</h1>
    <a href="{{ route('admin.ads.show', $ad) }}" class="text-sm text-gray-500 hover:underline">
        بازگشت به جزئیات آگهی
    </a>
</div>

<form
    method="POST"
    action="{{ route('admin.ads.update', $ad) }}"
    enctype="multipart/form-data"
    class="bg-white p-6 rounded-xl mt-5 max-w-3xl space-y-6"
>
    @csrf
    @method('PUT')

    @if ($errors->any())
        <div class="bg-red-50 text-red-700 rounded-lg p-3 text-sm space-y-1">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    {{-- نوع آگهی --}}
    <div>
        <label class="block mb-1 font-bold text-sm">نوع آگهی</label>
        <select name="type" id="adType" class="w-full rounded-lg border">
            <option value="product" @selected(old('type', $ad->type) === 'product')>محصول</option>
            <option value="service" @selected(old('type', $ad->type) === 'service')>خدمت</option>
        </select>
    </div>

    {{-- اطلاعات عمومی --}}
    <div class="grid sm:grid-cols-2 gap-4">
        <div class="sm:col-span-2">
            <label class="block mb-1 font-bold text-sm">عنوان</label>
            <input name="title" value="{{ old('title', $ad->title) }}" class="w-full rounded-lg border">
        </div>

        <div class="sm:col-span-2">
            <label class="block mb-1 font-bold text-sm">توضیحات</label>
            <textarea name="description" rows="4" class="w-full rounded-lg border">{{ old('description', $ad->description) }}</textarea>
        </div>

        <div>
            <label class="block mb-1 font-bold text-sm">قیمت (تومان)</label>
            <input type="number" step="1" min="0" name="price" value="{{ old('price', $ad->price) }}" class="w-full rounded-lg border">
        </div>

        <div>
            <label class="block mb-1 font-bold text-sm">دسته‌بندی</label>
            <select name="category_id" class="w-full rounded-lg border">
                @foreach ($categories as $cat)
                    <option
                        value="{{ $cat->id }}"
                        data-type="{{ $cat->type }}"
                        @selected(old('category_id', $ad->category_id) == $cat->id)
                    >
                        {{ $cat->name }} ({{ $cat->type === 'product' ? 'محصول' : 'خدمت' }})
                    </option>
                @endforeach
            </select>
        </div>
    </div>

    {{-- فیلدهای مخصوص محصول --}}
    <div id="productFields" class="grid sm:grid-cols-2 gap-4 border-t pt-4">
        <p class="sm:col-span-2 text-sm font-bold text-gray-500">فیلدهای مخصوص محصول</p>

        <div>
            <label class="block mb-1 font-bold text-sm">برند</label>
            <input name="brand" value="{{ old('brand', $ad->brand) }}" class="w-full rounded-lg border">
        </div>

        <div>
            <label class="block mb-1 font-bold text-sm">مدل</label>
            <input name="model" value="{{ old('model', $ad->model) }}" class="w-full rounded-lg border">
        </div>

        <div>
            <label class="block mb-1 font-bold text-sm">وضعیت کالا</label>
            <select name="condition" class="w-full rounded-lg border">
                <option value="">—</option>
                <option value="new" @selected(old('condition', $ad->condition) === 'new')>نو</option>
                <option value="used" @selected(old('condition', $ad->condition) === 'used')>کارکرده</option>
            </select>
        </div>

        <div>
            <label class="block mb-1 font-bold text-sm">شماره شبا (بدون IR)</label>
            <div dir="ltr" class="flex items-stretch border rounded-lg overflow-hidden">
                <span class="flex items-center px-3 bg-gray-100 font-bold text-gray-500">IR</span>
                <input
                    name="card_number"
                    value="{{ old('card_number', $ad->card_number) }}"
                    maxlength="24"
                    dir="ltr"
                    class="flex-1 border-0"
                >
            </div>
            <p class="text-xs text-gray-400 mt-1">این شماره فقط برای واریز وجه به فروشنده استفاده می‌شود.</p>
        </div>
    </div>

    {{-- فیلدهای مخصوص خدمت --}}
    <div id="serviceFields" class="grid sm:grid-cols-2 gap-4 border-t pt-4">
        <p class="sm:col-span-2 text-sm font-bold text-gray-500">فیلدهای مخصوص خدمت</p>

        <div>
            <label class="block mb-1 font-bold text-sm">نام و نام خانوادگی ارائه‌دهنده</label>
            <input name="full_name" value="{{ old('full_name', $ad->full_name) }}" class="w-full rounded-lg border">
        </div>

        <div>
            <label class="block mb-1 font-bold text-sm">عنوان خدمت</label>
            <input name="service_title" value="{{ old('service_title', $ad->service_title) }}" class="w-full rounded-lg border">
        </div>

        <div class="sm:col-span-2">
            <label class="block mb-1 font-bold text-sm">وب‌سایت</label>
            <input name="website" value="{{ old('website', $ad->website) }}" class="w-full rounded-lg border" dir="ltr">
        </div>
    </div>

    {{-- موقعیت و تماس --}}
    <div class="grid sm:grid-cols-2 gap-4 border-t pt-4">
        <div>
            <label class="block mb-1 font-bold text-sm">استان</label>
            <select name="province_id" id="province" class="w-full rounded-lg border">
                @foreach ($provinces as $p)
                    <option value="{{ $p->id }}" @selected(old('province_id', $ad->province_id) == $p->id)>
                        {{ $p->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block mb-1 font-bold text-sm">شهر</label>
            <select name="city_id" id="city" class="w-full rounded-lg border">
                @foreach ($provinces as $p)
                    @foreach ($p->cities as $c)
                        <option
                            value="{{ $c->id }}"
                            data-province="{{ $p->id }}"
                            @selected(old('city_id', $ad->city_id) == $c->id)
                        >
                            {{ $c->name }}
                        </option>
                    @endforeach
                @endforeach
            </select>
        </div>

        <div class="sm:col-span-2">
            <label class="block mb-1 font-bold text-sm">آدرس</label>
            <input name="address" value="{{ old('address', $ad->address) }}" class="w-full rounded-lg border">
        </div>

        <div>
            <label class="block mb-1 font-bold text-sm">تلفن</label>
            <input name="phone" value="{{ old('phone', $ad->phone) }}" class="w-full rounded-lg border" dir="ltr">
        </div>
    </div>

    {{-- تصاویر --}}
    <div class="border-t pt-4">
        <p class="text-sm font-bold text-gray-500 mb-3">تصاویر آگهی</p>

        <div class="grid sm:grid-cols-3 gap-3">
            @foreach ($ad->images as $img)
                <div class="border rounded-lg p-2 space-y-2">
                    <img src="{{ Storage::url($img->path) }}" class="h-32 w-full object-cover rounded">

                    <label class="flex items-center gap-1 text-xs">
                        <input type="radio" name="primary_image_id" value="{{ $img->id }}" @checked($img->is_primary)>
                        تصویر اصلی
                    </label>

                    <label class="flex items-center gap-1 text-xs text-red-600">
                        <input type="checkbox" name="delete_images[]" value="{{ $img->id }}">
                        حذف این تصویر
                    </label>
                </div>
            @endforeach
        </div>

        <div class="mt-4">
            <label class="block mb-1 font-bold text-sm">افزودن تصویر جدید</label>
            <input type="file" name="images[]" multiple accept="image/jpeg,image/png,image/webp" class="w-full">
            <p class="text-xs text-gray-400 mt-1">حداکثر ۱۰ تصویر، هر کدام تا ۱۰ مگابایت.</p>
        </div>
    </div>

    {{-- وضعیت و کنترل‌های مدیریتی --}}
    <div class="grid sm:grid-cols-2 gap-4 border-t pt-4">
        <div>
            <label class="block mb-1 font-bold text-sm">وضعیت انتشار</label>
            <select name="status" class="w-full rounded-lg border">
                <option value="pending" @selected($ad->status === 'pending')>در انتظار</option>
                <option value="approved" @selected($ad->status === 'approved')>تأیید</option>
                <option value="rejected" @selected($ad->status === 'rejected')>رد</option>
            </select>
        </div>

        <div>
            <label class="block mb-1 font-bold text-sm">تاریخ انقضا</label>
            <input
                name="expires_at"
                type="datetime-local"
                value="{{ optional($ad->expires_at)->format('Y-m-d\TH:i') }}"
                class="w-full rounded-lg border"
            >
        </div>

        <div class="sm:col-span-2">
            <label class="block mb-1 font-bold text-sm">دلیل رد (در صورت رد آگهی)</label>
            <textarea name="rejection_reason" class="w-full rounded-lg border">{{ old('rejection_reason', $ad->rejection_reason) }}</textarea>
        </div>

        <label class="flex items-center gap-2">
            <input type="checkbox" name="is_featured" value="1" @checked(old('is_featured', $ad->is_featured))>
            آگهی ویژه
        </label>

        <label class="flex items-center gap-2">
            <input type="checkbox" name="is_suspended" value="1" @checked(old('is_suspended', $ad->is_suspended))>
            تعلیق آگهی (بابت پایان اشتراک خدمت/محصول)
        </label>
    </div>

    <p class="text-xs text-gray-400">
        بازدید: {{ $ad->views_count }} — ثبت‌شده در {{ $ad->created_at->format('Y/m/d H:i') }} —
        آخرین ویرایش {{ $ad->updated_at->format('Y/m/d H:i') }}
    </p>

    <button class="btn-primary">ذخیره تغییرات</button>
</form>

<script>
(() => {
    const typeSelect = document.getElementById('adType');
    const productFields = document.getElementById('productFields');
    const serviceFields = document.getElementById('serviceFields');
    const categorySelect = document.querySelector('select[name="category_id"]');
    const provinceSelect = document.getElementById('province');
    const citySelect = document.getElementById('city');

    function syncTypeFields() {
        const isProduct = typeSelect.value === 'product';
        productFields.style.display = isProduct ? '' : 'none';
        serviceFields.style.display = isProduct ? 'none' : '';

        [...categorySelect.options].forEach(opt => {
            opt.hidden = opt.dataset.type !== typeSelect.value;
        });
    }

    function syncCityOptions() {
        const provinceId = provinceSelect.value;
        let hasSelected = false;

        [...citySelect.options].forEach(opt => {
            const match = opt.dataset.province === provinceId;
            opt.hidden = !match;
            if (match && opt.selected) hasSelected = true;
        });

        if (!hasSelected) {
            const firstMatch = [...citySelect.options].find(o => o.dataset.province === provinceId);
            if (firstMatch) firstMatch.selected = true;
        }
    }

    typeSelect.addEventListener('change', syncTypeFields);
    provinceSelect.addEventListener('change', syncCityOptions);

    syncTypeFields();
    syncCityOptions();
})();
</script>

@endsection
