@extends('admin.layouts.app')

@section('title','درخواست‌های ویرایش آگهی')

@section('content')

<div style="display:flex;justify-content:space-between;align-items:center;gap:16px;flex-wrap:wrap;margin-bottom:22px;">
    <div>
        <h1 style="font-size:1.4rem;">درخواست‌های ویرایش آگهی</h1>
        <p style="color:var(--color-steel);margin-top:6px;font-size:.9rem;">
            ارائه‌دهنده آگهی‌اش را ویرایش کرده است. تا وقتی تأیید نکنید، نسخه‌ی فعلی روی سایت می‌ماند.
        </p>
    </div>

    @if($pendingCount)
        <span style="background:var(--color-amber-bg);color:var(--color-amber);font-weight:800;padding:8px 16px;border-radius:999px;">
            {{ $pendingCount }} درخواست در انتظار بررسی
        </span>
    @endif
</div>

{{-- فیلتر وضعیت --}}
<div style="display:flex;gap:8px;margin-bottom:18px;flex-wrap:wrap;">
    @foreach(['pending'=>'در انتظار بررسی','approved'=>'تأییدشده','rejected'=>'ردشده','all'=>'همه'] as $key => $label)
        <a href="{{ route('admin.ad-edits.index', ['status' => $key]) }}"
           class="btn btn-sm {{ $status === $key ? 'btn-navy' : 'btn-ghost' }}">{{ $label }}</a>
    @endforeach
</div>

@if($edits->isEmpty())

    <div class="corner-card" style="padding:40px;text-align:center;color:var(--color-steel);">
        درخواستی در این وضعیت وجود ندارد.
    </div>

@else

    <div class="corner-card" style="padding:0;overflow-x:auto;">
        <table style="width:100%;border-collapse:collapse;">
            <thead>
                <tr style="background:var(--color-paper);">
                    <th style="padding:12px;text-align:right;font-size:.82rem;">آگهی</th>
                    <th style="padding:12px;text-align:right;font-size:.82rem;">ارائه‌دهنده</th>
                    <th style="padding:12px;text-align:right;font-size:.82rem;">تغییرات</th>
                    <th style="padding:12px;text-align:right;font-size:.82rem;">تاریخ</th>
                    <th style="padding:12px;text-align:right;font-size:.82rem;">وضعیت</th>
                    <th style="padding:12px;"></th>
                </tr>
            </thead>
            <tbody>
                @foreach($edits as $edit)
                    <tr style="border-top:1px solid var(--color-line);">

                        <td style="padding:12px;">
                            <div style="font-weight:700;">{{ $edit->ad?->title ?? '— حذف‌شده —' }}</div>
                            <div style="font-size:.75rem;color:var(--color-steel-light);">
                                {{ $edit->ad?->type === 'product' ? 'محصول' : 'خدمت' }}
                            </div>
                        </td>

                        <td style="padding:12px;font-size:.85rem;">
                            {{ $edit->user?->username ?: $edit->user?->name }}
                        </td>

                        <td style="padding:12px;font-size:.82rem;color:var(--color-steel);">
                            @php
                                $parts = [];
                                if ($edit->payload) $parts[] = count($edit->payload) . ' فیلد';
                                if ($edit->added_images) $parts[] = count($edit->added_images) . ' تصویر جدید';
                                if ($edit->removed_image_ids) $parts[] = count($edit->removed_image_ids) . ' حذف تصویر';
                            @endphp
                            {{ implode('، ', $parts) ?: '—' }}
                        </td>

                        <td style="padding:12px;font-size:.8rem;white-space:nowrap;">
                            {{ $edit->created_at->format('Y/m/d H:i') }}
                        </td>

                        <td style="padding:12px;">
                            @if($edit->status === 'pending')
                                <span style="background:var(--color-amber-bg);color:var(--color-amber);padding:4px 10px;border-radius:999px;font-size:.75rem;font-weight:800;">در انتظار</span>
                            @elseif($edit->status === 'approved')
                                <span style="background:var(--color-green-bg);color:var(--color-green);padding:4px 10px;border-radius:999px;font-size:.75rem;font-weight:800;">تأیید شد</span>
                            @else
                                <span style="background:var(--color-red-bg);color:var(--color-red);padding:4px 10px;border-radius:999px;font-size:.75rem;font-weight:800;">رد شد</span>
                            @endif
                        </td>

                        <td style="padding:12px;text-align:left;">
                            <a class="btn btn-sm btn-navy" href="{{ route('admin.ad-edits.show', $edit) }}">
                                {{ $edit->status === 'pending' ? 'بررسی' : 'مشاهده' }}
                            </a>
                        </td>

                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div style="margin-top:20px;">{{ $edits->links() }}</div>

@endif

@endsection
