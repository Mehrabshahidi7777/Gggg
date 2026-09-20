<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Ad;
use App\Models\AdRating;
use Illuminate\Http\Request;

class AdRatingController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | ثبت / ویرایش امتیاز یک آگهی
    |--------------------------------------------------------------------------
    |
    | هر کاربرِ واردشده می‌تواند به هر آگهیِ تأییدشده یک امتیاز ۱ تا ۵
    | بدهد. ارسال دوباره امتیاز قبلی را به‌روز می‌کند (updateOrCreate
    | روی قید یکتای (ad_id, user_id)) و رکورد تکراری نمی‌سازد.
    |
    */
    public function store(Request $request, Ad $ad)
    {
        /*
        | فقط آگهی‌ای که واقعاً برای عموم قابل مشاهده است امتیاز می‌گیرد.
        | آگهیِ در انتظار تأیید، ردشده، تعلیق‌شده یا منقضی نباید از راه
        | ساختن دستیِ آدرس امتیاز بگیرد.
        */
        abort_unless(
            $ad->status === 'approved'
            && !$ad->is_suspended
            && (!$ad->expires_at || $ad->expires_at->isFuture()),
            404
        );

        /*
        | جلوگیری از امتیازدهی به آگهی خود - وگرنه هر فروشنده می‌تواند
        | آگهی خودش را ۵ ستاره کند.
        */
        if ($ad->user_id === $request->user()->id) {
            return $this->respond(
                $request,
                $ad,
                false,
                'به آگهی خودتان نمی‌توانید امتیاز بدهید.'
            );
        }

        $data = $request->validate(
            [
                'rating' => ['required', 'integer', 'between:1,5'],
                'comment' => ['nullable', 'string', 'max:1000'],
            ],
            [
                'rating.required' => 'یک امتیاز از ۱ تا ۵ ستاره انتخاب کنید.',
                'rating.between' => 'امتیاز باید بین ۱ تا ۵ ستاره باشد.',
                'comment.max' => 'متن نظر حداکثر ۱۰۰۰ نویسه می‌تواند باشد.',
            ]
        );

        $comment = trim((string) ($data['comment'] ?? ''));

        $existing = AdRating::where('ad_id', $ad->id)
            ->where('user_id', $request->user()->id)
            ->first();

        $attributes = ['rating' => $data['rating']];

        /*
        |--------------------------------------------------------------------------
        | نظر متنی
        |--------------------------------------------------------------------------
        |
        | ستاره بلافاصله در میانگین اعمال می‌شود، اما متن تا تأیید
        | مدیر روی صفحه دیده نمی‌شود.
        |
        | نکته: اگر کاربر متنِ قبلاً تأییدشده‌اش را ویرایش کند، دوباره
        | به صف بررسی می‌رود. در غیر این صورت می‌شد یک متن بی‌خطر
        | نوشت، تأیید گرفت و بعد محتوایش را عوض کرد - همان حفره‌ای که
        | در ویرایش آگهی هم بسته شده است.
        */
        if ($comment !== '') {

            $changed = ! $existing || trim((string) $existing->comment) !== $comment;

            $attributes['comment'] = $comment;

            if ($changed) {
                $attributes['comment_status'] = 'pending';
                $attributes['comment_rejection_reason'] = null;
                $attributes['comment_reviewed_by'] = null;
                $attributes['comment_reviewed_at'] = null;
            }

        } elseif ($existing && $existing->comment) {

            // کاربر متنش را پاک کرده: نظر حذف می‌شود ولی امتیاز می‌ماند
            $attributes['comment'] = null;
            $attributes['comment_status'] = null;
            $attributes['comment_rejection_reason'] = null;
            $attributes['comment_reviewed_by'] = null;
            $attributes['comment_reviewed_at'] = null;
        }

        $rating = AdRating::updateOrCreate(
            [
                'ad_id' => $ad->id,
                'user_id' => $request->user()->id,
            ],
            $attributes
        );

        $message = 'امتیاز شما ثبت شد: ' . AdRating::labelFor($data['rating']);

        if ($rating->comment_status === 'pending') {
            $message .= ' — نظر شما ثبت شد و به‌زودی روی این صفحه نمایش داده می‌شود.';
        }

        return $this->respond($request, $ad, true, $message);
    }

    /*
    |--------------------------------------------------------------------------
    | پاسخ
    |--------------------------------------------------------------------------
    |
    | ستاره‌ها هم روی کارت‌های فهرست (با AJAX، بدون ترک صفحه) و هم روی
    | صفحه‌ی خود آگهی (ارسال معمولی فرم) استفاده می‌شوند، پس هر دو حالت
    | پاسخ داده می‌شود.
    |
    */
    private function respond(Request $request, Ad $ad, bool $success, string $message)
    {
        if ($request->expectsJson()) {

            $ad->loadCount('ratings')->loadAvg('ratings', 'rating');

            return response()->json([
                'success' => $success,
                'message' => $message,
                'average' => $ad->rating_average,
                'count' => $ad->rating_count,
            ], $success ? 200 : 422);
        }

        return back()->with($success ? 'success' : 'error', $message);
    }
}
