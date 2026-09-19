@extends('admin.layouts.app')

@section('title','نظرهای کاربران')

@section('content')

<div style="display:flex;justify-content:space-between;align-items:center;gap:16px;flex-wrap:wrap;margin-bottom:22px;">
    <div>
        <h1 style="font-size:1.4rem;">نظرهای کاربران</h1>
        <p style="color:var(--color-steel);margin-top:6px;font-size:.9rem;">
            امتیاز ستاره‌ای بلافاصله اعمال می‌شود، اما متن نظر تا تأیید شما روی صفحه‌ی آگهی دیده نمی‌شود.
        </p>
    </div>

    @if($pendingCount)
        <span style="background:var(--color-amber-bg);color:var(--color-amber);font-weight:800;padding:8px 16px;border-radius:999px;">
            {{ $pendingCount }} نظر در انتظار بررسی
        </span>
    @endif
</div>

<div style="display:flex;gap:8px;margin-bottom:18px;flex-wrap:wrap;">
    @foreach(['pending'=>'در انتظار بررسی','approved'=>'تأییدشده','rejected'=>'ردشده','all'=>'همه'] as $key => $label)
        <a href="{{ route('admin.comments.index', ['status' => $key]) }}"
           class="btn btn-sm {{ $status === $key ? 'btn-navy' : 'btn-ghost' }}">{{ $label }}</a>
    @endforeach
</div>

@if($comments->isEmpty())

    <div class="corner-card" style="padding:40px;text-align:center;color:var(--color-steel);">
        نظری در این وضعیت وجود ندارد.
    </div>

@else

    @foreach($comments as $comment)

        <div class="corner-card" style="padding:20px;margin-bottom:14px;">

            <div style="display:flex;justify-content:space-between;gap:14px;flex-wrap:wrap;align-items:flex-start;">

                <div style="flex:1;min-width:260px;">

                    <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                        <strong>{{ $comment->user?->username ?: $comment->user?->name ?? 'کاربر حذف‌شده' }}</strong>

                        <span style="color:var(--color-orange);letter-spacing:2px;" title="{{ \App\Models\AdRating::labelFor($comment->rating) }}">
                            {{ str_repeat('★', $comment->rating) }}{{ str_repeat('☆', 5 - $comment->rating) }}
                        </span>

                        <span style="font-size:.75rem;color:var(--color-steel-light);">
                            {{ \App\Models\AdRating::labelFor($comment->rating) }}
                        </span>

                        <span style="font-size:.75rem;color:var(--color-steel-light);">
                            {{ $comment->created_at->format('Y/m/d H:i') }}
                        </span>
                    </div>

                    <div style="font-size:.82rem;color:var(--color-steel-light);margin-top:6px;">
                        روی آگهی:
                        @if($comment->ad)
                            <a href="{{ route('ad.show', $comment->ad->slug) }}" target="_blank" rel="noopener"
                               style="color:var(--color-blueprint);font-weight:700;">{{ $comment->ad->title }}</a>
                        @else
                            — حذف‌شده —
                        @endif
                    </div>

                    <p style="margin-top:12px;line-height:1.9;white-space:pre-line;background:var(--color-paper);padding:14px;border-radius:var(--radius-sm);">{{ $comment->comment }}</p>

                    @if($comment->comment_status === 'rejected' && $comment->comment_rejection_reason)
                        <p style="margin-top:10px;color:var(--color-red);font-size:.85rem;">
                            دلیل رد: {{ $comment->comment_rejection_reason }}
                        </p>
                    @endif

                </div>

                <div style="min-width:220px;">

                    @if($comment->comment_status === 'pending')

                        <form method="POST" action="{{ route('admin.comments.approve', $comment) }}" style="margin-bottom:10px;">
                            @csrf
                            <button class="btn btn-success btn-block btn-sm" type="submit">تأیید و انتشار</button>
                        </form>

                        <form method="POST" action="{{ route('admin.comments.reject', $comment) }}">
                            @csrf
                            <input type="text" name="comment_rejection_reason" maxlength="500"
                                   placeholder="دلیل رد (اختیاری)"
                                   style="width:100%;padding:9px 12px;border:1.5px solid var(--color-line);border-radius:var(--radius-sm);font-size:.82rem;margin-bottom:8px;">
                            <button class="btn btn-danger btn-block btn-sm" type="submit">رد نظر</button>
                        </form>

                        <p style="font-size:.72rem;color:var(--color-steel-light);margin-top:10px;line-height:1.7;">
                            رد کردن متن، امتیاز ستاره‌ای این کاربر را حذف نمی‌کند.
                        </p>

                    @elseif($comment->comment_status === 'approved')
                        <span style="display:block;text-align:center;background:var(--color-green-bg);color:var(--color-green);padding:10px;border-radius:var(--radius-sm);font-weight:800;font-size:.82rem;">
                            تأیید شده
                        </span>
                    @else
                        <span style="display:block;text-align:center;background:var(--color-red-bg);color:var(--color-red);padding:10px;border-radius:var(--radius-sm);font-weight:800;font-size:.82rem;">
                            رد شده
                        </span>
                    @endif

                </div>

            </div>

        </div>

    @endforeach

    <div style="margin-top:20px;">{{ $comments->links() }}</div>

@endif

@endsection
