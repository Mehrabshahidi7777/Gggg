@extends('admin.layouts.app')

@section('title','بررسی ویرایش آگهی')

@section('content')

<div style="margin-bottom:22px;">
    <a href="{{ route('admin.ad-edits.index') }}" style="color:var(--color-blueprint);font-weight:700;">← بازگشت به فهرست</a>
    <h1 style="font-size:1.4rem;margin-top:10px;">بررسی ویرایش آگهی</h1>
</div>

<div class="corner-card" style="padding:22px;margin-bottom:20px;">
    <div class="sz-grid sz-grid-3" style="gap:16px;">
        <div>
            <div style="font-size:.75rem;color:var(--color-steel-light);">آگهی</div>
            <div style="font-weight:800;margin-top:4px;">
                @if($edit->ad)
                    <a href="{{ route('ad.show', $edit->ad->slug) }}" target="_blank" rel="noopener"
                       style="color:var(--color-blueprint);">{{ $edit->ad->title }}</a>
                @else
                    — حذف‌شده —
                @endif
            </div>
        </div>
        <div>
            <div style="font-size:.75rem;color:var(--color-steel-light);">ارائه‌دهنده</div>
            <div style="font-weight:800;margin-top:4px;">{{ $edit->user?->username ?: $edit->user?->name }}</div>
        </div>
        <div>
            <div style="font-size:.75rem;color:var(--color-steel-light);">تاریخ ثبت</div>
            <div style="font-weight:800;margin-top:4px;">{{ $edit->created_at->format('Y/m/d H:i') }}</div>
        </div>
    </div>
</div>

{{--
    جدول تفاوت‌ها: ستون راست مقدار فعلی آگهی، ستون چپ مقداری که
    ارائه‌دهنده پیشنهاد داده.
--}}
@if($diff)
    <h2 style="font-size:1.05rem;margin-bottom:12px;">تغییرات اطلاعات</h2>

    <div class="corner-card" style="padding:0;overflow-x:auto;margin-bottom:22px;">
        <table style="width:100%;border-collapse:collapse;">
            <thead>
                <tr style="background:var(--color-paper);">
                    <th style="padding:12px;text-align:right;font-size:.82rem;width:150px;">فیلد</th>
                    <th style="padding:12px;text-align:right;font-size:.82rem;">مقدار فعلی</th>
                    <th style="padding:12px;text-align:right;font-size:.82rem;">مقدار پیشنهادی</th>
                </tr>
            </thead>
            <tbody>
                @foreach($diff as $row)
                    <tr style="border-top:1px solid var(--color-line);">
                        <td style="padding:12px;font-weight:800;font-size:.85rem;vertical-align:top;">{{ $row['label'] }}</td>

                        <td style="padding:12px;font-size:.85rem;vertical-align:top;background:var(--color-red-bg);">
                            <span style="color:var(--color-red);white-space:pre-line;">{{ $row['old'] }}</span>
                        </td>

                        <td style="padding:12px;font-size:.85rem;vertical-align:top;background:var(--color-green-bg);">
                            <span style="color:var(--color-green);font-weight:700;white-space:pre-line;">{{ $row['new'] }}</span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@else
    <div class="corner-card" style="padding:18px;margin-bottom:22px;color:var(--color-steel);">
        هیچ فیلد متنی تغییر نکرده است؛ این درخواست فقط تصاویر را تغییر می‌دهد.
    </div>
@endif

{{-- تصاویری که قرار است حذف شوند --}}
@if($removedImages->count())
    <h2 style="font-size:1.05rem;margin-bottom:12px;color:var(--color-red);">تصاویری که حذف می‌شوند</h2>
    <div class="sz-grid sz-grid-4" style="margin-bottom:22px;">
        @foreach($removedImages as $image)
            <img src="{{ Storage::url($image->path) }}" alt=""
                 style="width:100%;height:130px;object-fit:cover;border-radius:var(--radius-sm);border:3px solid var(--color-red);opacity:.65;">
        @endforeach
    </div>
@endif

{{-- تصاویری که اضافه می‌شوند --}}
@if($edit->added_images)
    <h2 style="font-size:1.05rem;margin-bottom:12px;color:var(--color-green);">تصاویری که اضافه می‌شوند</h2>
    <div class="sz-grid sz-grid-4" style="margin-bottom:22px;">
        @foreach($edit->added_images as $path)
            <img src="{{ Storage::url($path) }}" alt=""
                 style="width:100%;height:130px;object-fit:cover;border-radius:var(--radius-sm);border:3px solid var(--color-green);">
        @endforeach
    </div>
@endif

{{-- اقدام --}}
@if($edit->status === 'pending')

    <div class="corner-card" style="padding:22px;">

        <div style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-start;">

            <form method="POST" action="{{ route('admin.ad-edits.approve', $edit) }}">
                @csrf
                <button class="btn btn-success" type="submit">تأیید و اعمال روی آگهی</button>
            </form>

            <form method="POST" action="{{ route('admin.ad-edits.reject', $edit) }}"
                  style="flex:1;min-width:280px;display:flex;gap:10px;align-items:flex-start;">
                @csrf
                <input type="text" name="rejection_reason" required maxlength="1000"
                       placeholder="دلیل رد درخواست (برای کاربر نمایش داده می‌شود)"
                       style="flex:1;padding:12px 14px;border:1.5px solid var(--color-line);border-radius:var(--radius-sm);">
                <button class="btn btn-danger" type="submit">رد درخواست</button>
            </form>

        </div>

        @error('rejection_reason')
            <p style="color:var(--color-red);margin-top:10px;font-size:.85rem;">{{ $message }}</p>
        @enderror

    </div>

@else

    <div class="corner-card" style="padding:22px;">
        <strong>
            این درخواست در {{ $edit->reviewed_at?->format('Y/m/d H:i') }}
            توسط {{ $edit->reviewer?->username ?: $edit->reviewer?->name ?? 'مدیر' }}
            {{ $edit->status === 'approved' ? 'تأیید' : 'رد' }} شده است.
        </strong>

        @if($edit->rejection_reason)
            <p style="margin-top:10px;color:var(--color-red);">دلیل: {{ $edit->rejection_reason }}</p>
        @endif
    </div>

@endif

@endsection
