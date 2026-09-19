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

            <div class="form-grid-2" style="display:grid;grid-template-columns:1fr 1fr;gap:18px;">

                <label style="grid-column:1/-1;">
                    <span class="field-label">عنوان آگهی *</span>
                    <input type="text" name="title" value="{{ old('title', $ad->title) }}" required maxlength="255">
                </label>

                <label>
                    <span class="field-label">دسته‌بندی *</span>
                    <select name="category_id" required>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" @selected(old('category_id',$ad->category_id)==$category->id)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                </label>

                <label>
                    <span class="field-label">قیمت (تومان)</span>
                    <input type="text" name="price" value="{{ old('price', $ad->price ? (int) $ad->price : '') }}" inputmode="numeric">
                </label>

                <label>
                    <span class="field-label">استان *</span>
                    <select name="province_id" id="edit-province" required>
                        @foreach($provinces as $province)
                            <option value="{{ $province->id }}" @selected(old('province_id',$ad->province_id)==$province->id)>{{ $province->name }}</option>
                        @endforeach
                    </select>
                </label>

                <label>
                    <span class="field-label">شهر *</span>
                    <select name="city_id" id="edit-city" required
                            data-selected="{{ old('city_id', $ad->city_id) }}"></select>
                </label>

                @if($ad->type === 'product')

                    <label>
                        <span class="field-label">برند</span>
                        <input type="text" name="brand" value="{{ old('brand', $ad->brand) }}" maxlength="100">
                    </label>

                    <label>
                        <span class="field-label">مدل</span>
                        <input type="text" name="model" value="{{ old('model', $ad->model) }}" maxlength="100">
                    </label>

                    <label>
                        <span class="field-label">وضعیت کالا</span>
                        <select name="condition">
                            <option value="">—</option>
                            <option value="new" @selected(old('condition',$ad->condition)==='new')>نو</option>
                            <option value="used" @selected(old('condition',$ad->condition)==='used')>کارکرده</option>
                        </select>
                    </label>

                    <label>
                        <span class="field-label">شماره شبا (۲۴ رقم، بدون IR) *</span>
                        <input type="text" name="card_number" value="{{ old('card_number', $ad->card_number) }}" inputmode="numeric" required>
                    </label>

                @else

                    <label>
                        <span class="field-label">نام و نام خانوادگی *</span>
                        <input type="text" name="full_name" value="{{ old('full_name', $ad->full_name) }}" required maxlength="255">
                    </label>

                    <label>
                        <span class="field-label">عنوان تخصص *</span>
                        <input type="text" name="service_title" value="{{ old('service_title', $ad->service_title) }}" required maxlength="255">
                    </label>

                    <label style="grid-column:1/-1;">
                        <span class="field-label">وب‌سایت</span>
                        <input type="url" name="website" value="{{ old('website', $ad->website) }}" maxlength="255" dir="ltr">
                    </label>

                @endif

                <label>
                    <span class="field-label">شماره تماس *</span>
                    <input type="text" name="phone" value="{{ old('phone', $ad->phone) }}" required dir="ltr" inputmode="numeric" placeholder="09121234567">
                </label>

                <label style="grid-column:1/-1;">
                    <span class="field-label">آدرس *</span>
                    <input type="text" name="address" value="{{ old('address', $ad->address) }}" required maxlength="1000">
                </label>

                <label style="grid-column:1/-1;">
                    <span class="field-label">توضیحات</span>
                    <textarea name="description" rows="6" maxlength="5000">{{ old('description', $ad->description) }}</textarea>
                </label>

            </div>

            {{-- تصاویر فعلی --}}
            @if($ad->images->count())
                <div style="margin-top:24px;">
                    <span class="field-label">تصاویر فعلی — تیک بزنید تا حذف شوند</span>
                    <div class="sz-grid sz-grid-4" style="margin-top:10px;">
                        @foreach($ad->images as $image)
                            <label style="cursor:pointer;display:block;">
                                <img src="{{ Storage::url($image->path) }}" alt=""
                                     style="width:100%;height:110px;object-fit:cover;border-radius:var(--radius-sm);border:2px solid var(--color-line);">
                                <span style="display:flex;align-items:center;gap:6px;margin-top:6px;font-size:.8rem;">
                                    <input type="checkbox" name="delete_images[]" value="{{ $image->id }}">
                                    حذف این تصویر
                                </span>
                            </label>
                        @endforeach
                    </div>
                </div>
            @endif

            <label style="display:block;margin-top:20px;">
                <span class="field-label">افزودن تصویر جدید (حداکثر ۱۰ تصویر، هرکدام تا ۱۰ مگابایت)</span>
                <input type="file" name="images[]" accept="image/jpeg,image/png,image/webp" multiple>
            </label>

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
