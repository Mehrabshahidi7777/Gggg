<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Ad;
use App\Models\AdContactReveal;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;

class AdContactController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | نمایش شماره تماس آگهی
    |--------------------------------------------------------------------------
    |
    | شماره داخل HTML صفحه نیست و فقط از این مسیر برگردانده می‌شود.
    | مسیر throttle دارد، پس جمع‌آوری انبوه شماره‌ها دیگر با یک خزش
    | ساده ممکن نیست.
    |
    | هر نمایش ثبت می‌شود تا ارائه‌دهنده در پنلش ببیند آگهی‌اش چند
    | سرنخ واقعی آورده است.
    |
    */
    public function show(Request $request, Ad $ad)
    {
        abort_unless(
            $ad->status === 'approved'
            && ! $ad->is_suspended
            && (! $ad->expires_at || $ad->expires_at->isFuture()),
            404
        );

        if (! $ad->phone) {
            return response()->json([
                'success' => false,
                'message' => 'برای این آگهی شماره‌ای ثبت نشده است.',
            ], 404);
        }

        /*
        | مهمان باید اول حساب بسازد.
        |
        | این فقط جلوی جمع‌آوری شماره‌ها را نمی‌گیرد؛ کاری می‌کند که
        | «شماره‌هایی که دیده‌ام» در پنل کاربر معنی داشته باشد. تا وقتی
        | نمایش‌ها بی‌نام بودند، تنها چیزی که می‌شد ذخیره کرد هشِ IP
        | بود - که به هیچ حسابی وصل نیست و به درد کاربر نمی‌خورد.
        |
        | مقصد در سشن نگه داشته می‌شود تا بعد از ثبت‌نام، کاربر به همین
        | آگهی برگردد، نه به صفحه‌ی خانه.
        */
        if (! auth()->check()) {

            $request->session()->put('url.intended', route('ad.show', $ad->slug));

            return response()->json([
                'success' => false,
                'requires_auth' => true,
                'message' => 'برای دیدن شماره وارد شوید',
                'url' => route('register'),
            ], 401);
        }

        $this->record($request, $ad);

        $phone = normalize_mobile($ad->phone) ?: $ad->phone;

        return response()->json([
            'success' => true,
            'phone' => $phone,
            'tel' => 'tel:' . $phone,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | ثبت نمایش
    |--------------------------------------------------------------------------
    |
    | صاحب آگهی شمرده نمی‌شود - وگرنه هر بار که خودش آگهی‌اش را باز
    | کند آمار سرنخ‌هایش باد می‌کند و عدد بی‌معنی می‌شود.
    |
    */
    private function record(Request $request, Ad $ad): void
    {
        if (auth()->check() && auth()->id() === $ad->user_id) {
            return;
        }

        /*
        | IP خام ذخیره نمی‌شود. هش با APP_KEY نمک‌گذاری می‌شود، پس
        | نه قابل بازگشت به IP است و نه بین نصب‌های مختلف قابل تطبیق.
        */
        $ipHash = hash_hmac('sha256', (string) $request->ip(), (string) config('app.key'));

        try {

            AdContactReveal::create([
                'ad_id' => $ad->id,
                'user_id' => auth()->id(),
                'ip_hash' => $ipHash,
                'revealed_on' => now()->toDateString(),
            ]);

        } catch (QueryException $e) {

            /*
            | کلید یکتای (ad_id, ip_hash, revealed_on) یعنی همین
            | بازدیدکننده امروز قبلاً شمرده شده. این خطا کاملاً مورد
            | انتظار است و نباید جلوی نمایش شماره را بگیرد.
            */
        }
    }
}
