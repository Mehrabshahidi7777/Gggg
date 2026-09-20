@extends('layouts.app')

@section('title', 'ویرایش آگهی: ' . $ad->title)

@section('content')

<div class="container" style="padding-top:32px;padding-bottom:60px;max-width:860px;">

    <div style="margin-bottom:24px;">
        <a href="{{ route($ad->type === 'product' ? 'product.panel' : 'service.panel') }}"
           style="color:var(--color-blueprint);font-weight:700;">← بازگشت به پنل</a>
        <h1 style="margin-top:10px;">ویرایش آگهی</h1>
        <p style="color:var(--color-steel);margin-top:6px;">{{ $ad->title }}</p>
    </div>

    {{--
        اگر درخواست بررسی‌نشده‌ای در صف است، فرم نمایش داده نمی‌شود.
        دو درخواست هم‌زمان برای یک آگهی یعنی ادمین نمی‌داند کدام را
        باید اعمال کند.
    --}}
    @if($pendingEdit)

        <div class="corner-card" style="padding:24px;border-inline-start:4px solid var(--color-amber);">
            <h2 style="font-size:1.05rem;color:var(--color-amber);">در انتظار تأیید مدیر</h2>

            <p style="margin-top:10px;line-height:1.9;">
                یک درخواست ویرایش برای این آگهی در تاریخ
                {{ $pendingEdit->created_at->format('Y/m/d H:i') }}
                ثبت شده و هنوز بررسی نشده است. تا تعیین تکلیف آن نمی‌توانید ویرایش تازه‌ای ثبت کنید.
            </p>

            <div style="margin-top:16px;background:var(--color-paper);border-radius:var(--radius-sm);padding:14px;">
                <strong style="font-size:.85rem;">تغییرات ثبت‌شده:</strong>
                <ul style="margin-top:8px;">
                    @foreach($pendingEdit->diff() as $row)
                        <li style="font-size:.85rem;padding:4px 0;">
                            <b>{{ $row['label'] }}:</b>
                            <span style="color:var(--color-steel-light);text-decoration:line-through;">{{ $row['old'] }}</span>
                            <span style="color:var(--color-green);">← {{ $row['new'] }}</span>
                        </li>
                    @endforeach

                    @if($pendingEdit->added_images)
                        <li style="font-size:.85rem;padding:4px 0;">
                            <b>تصاویر:</b> {{ count($pendingEdit->added_images) }} تصویر جدید
                        </li>
                    @endif

                    @if($pendingEdit->removed_image_ids)
                        <li style="font-size:.85rem;padding:4px 0;">
                            <b>تصاویر:</b> {{ count($pendingEdit->removed_image_ids) }} تصویر برای حذف
                        </li>
                    @endif
                </ul>
            </div>

            <form method="POST" action="{{ route('ad.edit.cancel', $ad) }}" style="margin-top:18px;">
                @csrf
                @method('DELETE')
                <button class="btn btn-danger btn-sm" type="submit">لغو درخواست و ویرایش دوباره</button>
            </form>
        </div>

    @else

        @if($ad->edits()->where('status','rejected')->exists())
            @php $lastRejected = $ad->edits()->where('status','rejected')->latest()->first(); @endphp
            <div class="corner-card" style="padding:18px;margin-bottom:20px;border-inline-start:4px solid var(--color-red);">
                <strong style="color:var(--color-red);">آخرین درخواست ویرایش شما تأیید نشد</strong>
                @if($lastRejected->rejection_reason)
                    <p style="margin-top:8px;line-height:1.9;">{{ $lastRejected->rejection_reason }}</p>
                @endif
            </div>
        @endif

        <div class="corner-card" style="padding:18px;margin-bottom:20px;background:var(--color-blueprint-tint);">
            <p style="line-height:1.9;margin:0;font-size:.9rem;">
                تغییرات شما بلافاصله روی سایت اعمال نمی‌شود. پس از ثبت، مدیر آن را بررسی می‌کند
                و تا آن زمان نسخه‌ی فعلی آگهی برای بازدیدکننده‌ها نمایش داده می‌شود.
            </p>
        </div>

        @if($errors->any())
            <div class="corner-card" style="padding:18px;margin-bottom:20px;border-inline-start:4px solid var(--color-red);">
                <ul>
                    @foreach($errors->all() as $error)
                        <li style="color:var(--color-red);">{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('ad.edit.store', $ad) }}" enctype="multipart/form-data" class="corner-card" style="padding:26px;">
            @csrf
            @method('PUT')

            {{--
                ساختار فیلدها عمداً همان الگوی استاندارد بقیه‌ی سایت است:

                    <div class="field"><label for="…">…</label><input id="…"></div>

                قبلاً این صفحه الگوی خودش را داشت - یک <label> که ورودی را
                در بر می‌گرفت و یک <span class="field-label"> برای متن. آن
                کلاس هیچ‌جای CSS تعریف نشده بود، پس برچسب inline می‌ماند و
                کنار ورودی می‌چسبید، و ورودی‌ها هم چون زیر .field نبودند
                width:100% نمی‌گرفتند و عرض ذاتی خودشان (حدود ۲۰ نویسه) را
                نگه می‌داشتند. ستون‌های grid هم به همان اندازه پهن می‌شدند
                و کل صفحه روی موبایل از عرض بیرون می‌زد.

                همچنین grid-template-columns اینجا inline نوشته شده بود.
                چون inline style از media query قوی‌تر است، قانون موجودِ
                «روی عرض کمتر از ۶۴۰ پیکسل تک‌ستونه شو» بی‌اثر می‌شد - در
                حالی که همان قانون برای فرم ثبت آگهی کار می‌کرد. حالا فقط
                کلاس گذاشته شده و هیچ استایل inlineای در کار نیست.
            --}}
            <div class="form-grid-2">

                <div class="field field--wide">
                    <label for="edit-title">عنوان آگهی *</label>
                    <input id="edit-title" type="text" name="title" value="{{ old('title', $ad->title) }}" required maxlength="255">
                </div>

                <div class="field">
                    <label for="edit-category">دسته‌بندی *</label>
                    <select id="edit-category" name="category_id" required>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" @selected(old('category_id',$ad->category_id)==$category->id)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="field">
                    <label for="edit-price">قیمت (تومان)</label>
                    <input id="edit-price" type="text" name="price" value="{{ old('price', $ad->price ? (int) $ad->price : '') }}" inputmode="numeric">
                </div>

                <div class="field">
                    <label for="edit-province">استان *</label>
                    <select id="edit-province" name="province_id" required>
                        @foreach($provinces as $province)
                            <option value="{{ $province->id }}" @selected(old('province_id',$ad->province_id)==$province->id)>{{ $province->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="field">
                    <label for="edit-city">شهر *</label>
                    <select id="edit-city" name="city_id" required
                            data-selected="{{ old('city_id', $ad->city_id) }}"></select>
                </div>

                @if($ad->type === 'product')

                    <div class="field">
                        <label for="edit-brand">برند</label>
                        <input id="edit-brand" type="text" name="brand" value="{{ old('brand', $ad->brand) }}" maxlength="100">
                    </div>

                    <div class="field">
                        <label for="edit-model">مدل</label>
                        <input id="edit-model" type="text" name="model" value="{{ old('model', $ad->model) }}" maxlength="100">
                    </div>

                    <div class="field">
                        <label for="edit-condition">وضعیت کالا</label>
                        <select id="edit-condition" name="condition">
                            <option value="">—</option>
                            <option value="new" @selected(old('condition',$ad->condition)==='new')>نو</option>
                            <option value="used" @selected(old('condition',$ad->condition)==='used')>کارکرده</option>
                        </select>
                    </div>

                    {{--
                        شماره شبا ۲۴ رقم است و در یک ستونِ نصف‌عرض جا
                        نمی‌شود؛ تمام عرض می‌گیرد و dir=ltr هم دارد تا
                        ارقام از چپ به راست تایپ شوند.
                    --}}
                    <div class="field field--wide">
                        <label for="edit-card">شماره شبا (۲۴ رقم، بدون IR) *</label>
                        <input id="edit-card" type="text" name="card_number" value="{{ old('card_number', $ad->card_number) }}"
                               inputmode="numeric" dir="ltr" maxlength="24" required>
                    </div>

                @else

                    <div class="field">
                        <label for="edit-fullname">نام و نام خانوادگی *</label>
                        <input id="edit-fullname" type="text" name="full_name" value="{{ old('full_name', $ad->full_name) }}" required maxlength="255">
                    </div>

                    <div class="field">
                        <label for="edit-service-title">عنوان تخصص *</label>
                        <input id="edit-service-title" type="text" name="service_title" value="{{ old('service_title', $ad->service_title) }}" required maxlength="255">
                    </div>

                    <div class="field field--wide">
                        <label for="edit-website">وب‌سایت</label>
                        <input id="edit-website" type="url" name="website" value="{{ old('website', $ad->website) }}" maxlength="255" dir="ltr">
                    </div>

                @endif

                <div class="field">
                    <label for="edit-phone">شماره تماس *</label>
                    <input id="edit-phone" type="text" name="phone" value="{{ old('phone', $ad->phone) }}"
                           required dir="ltr" inputmode="numeric" maxlength="11" placeholder="09121234567">
                </div>

                {{--
                    maxlength اینجا ۲۵۵ است، نه ۱۰۰۰. ستون address در
                    دیتابیس varchar(255) است و اعتبارسنجی سمت سرور هم روی
                    همان ۲۵۵ بسته شده. اگر مرورگر اجازه‌ی تایپ ۱۰۰۰ نویسه
                    بدهد، کاربر متن بلند می‌نویسد و موقع ثبت با خطا
                    روبه‌رو می‌شود - در حالی که می‌شد همان اول جلویش را
                    گرفت.
                --}}
                <div class="field field--wide">
                    <label for="edit-address">آدرس *</label>
                    <input id="edit-address" type="text" name="address" value="{{ old('address', $ad->address) }}" required maxlength="255">
                </div>

                <div class="field field--wide">
                    <label for="edit-description">توضیحات</label>
                    <textarea id="edit-description" name="description" rows="6" maxlength="5000">{{ old('description', $ad->description) }}</textarea>
                </div>

            </div>

            {{-- تصاویر فعلی --}}
            @if($ad->images->count())
                <div class="field">
                    <label>تصاویر فعلی</label>
                    <p class="hint" style="margin-bottom:10px;">
                        برای حذف یک تصویر، روی خودِ تصویر یا نوشته‌ی زیرش بزنید.
                        تصویرِ انتخاب‌شده کم‌رنگ و قرمز می‌شود. حذف پس از
                        ثبت درخواست و تأیید انجام می‌شود.
                    </p>
                    <div class="sz-grid sz-grid-4">
                        @foreach($ad->images as $image)
                            <label class="ad-edit-image">
                                <img src="{{ Storage::url($image->path) }}" alt="">
                                <span>
                                    <input type="checkbox" name="delete_images[]" value="{{ $image->id }}">
                                    حذف این تصویر
                                </span>
                            </label>
                        @endforeach
                    </div>
                </div>
            @endif

            {{--
                سقفِ این فرم «کل» نیست، «باقی‌مانده» است: هر ویرایش
                تصویر را به تصاویرِ موجود اضافه می‌کند. پس عددی که به
                کاربر نشان داده می‌شود هم باید همان باقی‌مانده باشد،
                وگرنه ۱۰ تا انتخاب می‌کند و پیام خطا می‌گیرد.

                تیک‌های حذف در این عدد حساب نمی‌شوند - آن‌ها فقط بعد
                از ثبت اعمال می‌شوند. سمت سرور حساب کامل را می‌کند و
                حرف آخر را می‌زند.
            --}}
            @php
                $remainingImages = max(0, \App\Models\Ad::MAX_IMAGES - $ad->images->count());
            @endphp

            <div class="field">
                <label for="edit-images">افزودن تصویر جدید</label>

                <p class="hint" style="margin-bottom:10px;">
                    @if($remainingImages > 0)
                        این آگهی {{ $ad->images->count() }} تصویر دارد و تا سقف {{ \App\Models\Ad::MAX_IMAGES }} تصویر،
                        <b>{{ $remainingImages }} تصویر دیگر</b> می‌توانید اضافه کنید. هرکدام تا ۱۰ مگابایت.
                    @else
                        این آگهی به سقف {{ \App\Models\Ad::MAX_IMAGES }} تصویر رسیده است.
                        برای افزودن تصویر تازه، اول چند تصویر بالا را برای حذف تیک بزنید و ثبت کنید.
                    @endif
                </p>

                <p class="hint" id="edit-images-note" style="margin-bottom:10px;"></p>

                <input
                    id="edit-images"
                    type="file"
                    name="images[]"
                    accept="image/jpeg,image/png,image/webp"
                    multiple
                    data-max-images="{{ \App\Models\Ad::MAX_IMAGES }}"
                    data-images-remaining="{{ $remainingImages }}"
                    data-images-note="#edit-images-note"
                >
            </div>

            <button class="btn btn-navy" type="submit" style="margin-top:26px;">ثبت درخواست ویرایش</button>

        </form>

    @endif

</div>

@push('scripts')
<script>
/*
  شهرها وابسته به استان‌اند. همان مسیر /api/cities که فرم ثبت آگهی
  استفاده می‌کند اینجا هم به‌کار می‌رود، با این تفاوت که شهر فعلیِ
  آگهی باید از ابتدا انتخاب‌شده باشد.
*/
document.addEventListener('DOMContentLoaded', () => {

    const province = document.getElementById('edit-province');
    const city = document.getElementById('edit-city');
    if (!province || !city) return;

    const load = async (selected) => {
        city.innerHTML = '<option>در حال بارگذاری…</option>';
        try {
            const response = await fetch('/api/cities/' + province.value);
            const cities = await response.json();
            city.innerHTML = '';
            cities.forEach(c => {
                const option = document.createElement('option');
                option.value = c.id;
                option.textContent = c.name;
                if (String(c.id) === String(selected)) option.selected = true;
                city.appendChild(option);
            });
        } catch (e) {
            city.innerHTML = '<option value="">خطا در بارگذاری شهرها</option>';
        }
    };

    load(city.dataset.selected);
    province.addEventListener('change', () => load(null));
});
</script>
@endpush

@endsection
