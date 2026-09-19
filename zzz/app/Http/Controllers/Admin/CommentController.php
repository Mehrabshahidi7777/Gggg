<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdRating;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class CommentController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | صف نظرها
    |--------------------------------------------------------------------------
    |
    | فقط رکوردهایی که واقعاً متن دارند. کسی که صرفاً ستاره داده
    | چیزی برای تأیید ندارد و نباید صف را شلوغ کند.
    |
    */
    public function index(Request $request)
    {
        $status = $request->input('status', 'pending');

        $comments = AdRating::query()
            ->whereNotNull('comment_status')
            ->with(['ad', 'user', 'commentReviewer'])
            ->when(
                in_array($status, ['pending', 'approved', 'rejected'], true),
                fn ($q) => $q->where('comment_status', $status)
            )
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.comments.index', [
            'comments' => $comments,
            'status' => $status,
            'pendingCount' => AdRating::where('comment_status', 'pending')->count(),
        ]);
    }

    public function approve(AdRating $comment)
    {
        if ($comment->comment_status !== 'pending') {
            return back()->with('error', 'این نظر قبلاً بررسی شده است.');
        }

        $comment->update([
            'comment_status' => 'approved',
            'comment_rejection_reason' => null,
            'comment_reviewed_by' => auth()->id(),
            'comment_reviewed_at' => now(),
        ]);

        Cache::forget('admin.pending_comments');

        return back()->with('success', 'نظر تأیید شد و روی صفحه‌ی آگهی نمایش داده می‌شود.');
    }

    public function reject(Request $request, AdRating $comment)
    {
        if ($comment->comment_status !== 'pending') {
            return back()->with('error', 'این نظر قبلاً بررسی شده است.');
        }

        $data = $request->validate([
            'comment_rejection_reason' => ['nullable', 'string', 'max:500'],
        ]);

        /*
        | امتیاز ستاره‌ای دست نمی‌خورد.
        |
        | رد شدنِ متنِ یک نظر به این معنی نیست که امتیاز آن کاربر هم
        | نامعتبر است؛ ممکن است فقط لحن متن مشکل داشته باشد. پاک‌کردن
        | امتیاز در این حالت یعنی تنبیه کاربر بابت چیزی که نظر
        | نداده.
        */
        $comment->update([
            'comment_status' => 'rejected',
            'comment_rejection_reason' => $data['comment_rejection_reason'] ?? null,
            'comment_reviewed_by' => auth()->id(),
            'comment_reviewed_at' => now(),
        ]);

        Cache::forget('admin.pending_comments');

        return back()->with('success', 'نظر رد شد. امتیاز ستاره‌ای کاربر دست‌نخورده باقی ماند.');
    }
}
