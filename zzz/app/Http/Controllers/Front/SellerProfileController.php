<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Ad;
use App\Models\AdContactReveal;
use App\Models\User;

class SellerProfileController extends Controller
{
    public function show(User $user)
    {
        $products = $user->ads()->approved()->where('type','product')->with(['category','province','city','primaryImage'])->withRatingSummary()->latest()->paginate(12, ['*'], 'products_page');
        $services = $user->ads()->approved()->where('type','service')->with(['category','province','city','primaryImage'])->withRatingSummary()->latest()->limit(8)->get();
        $reviewQuery = $user->receivedReviews()->with('buyer')->latest();
        $reviews = $reviewQuery->paginate(8, ['*'], 'reviews_page');
        $averageRating = round((float) ($user->receivedReviews()->avg('rating') ?? 0), 1);
        $reviewCount = $user->receivedReviews()->count();

        /*
        |--------------------------------------------------------------------------
        | «قلم تکمیل‌شده» فقط وقتی خرید آنلاین روشن است
        |--------------------------------------------------------------------------
        |
        | این عدد سفارش‌های تکمیل‌شده‌ی سبد خرید را می‌شمارد. خرید آنلاین
        | در سایت خاموش است (marketplace.online_checkout) و معامله با تماس
        | مستقیم انجام می‌شود - پس این عدد برای همه‌ی فروشنده‌ها همیشه صفر
        | بود و به خریدار می‌گفت «این فروشنده هیچ‌چیز نفروخته».
        |
        | null یعنی «نشانش نده»، نه صفر. اگر روزی خرید آنلاین روشن شود،
        | خودش برمی‌گردد.
        */
        $salesCount = config('marketplace.online_checkout')
            ? (int) $user->orderItems()->where('status','completed')->sum('quantity')
            : null;

        /*
        |--------------------------------------------------------------------------
        | چند نفر شماره‌ی این فروشنده را دیده‌اند
        |--------------------------------------------------------------------------
        |
        | جای «قلم تکمیل‌شده» را می‌گیرد، چون کاری است که خریدارِ این سایت
        | واقعاً انجام می‌دهد.
        |
        | ⚠️ آدم شمرده می‌شود، نه دفعه. جدول برای هر آگهی و هر کاربر روزی
        | یک ردیف نگه می‌دارد؛ اگر ردیف‌ها را می‌شمردیم، کسی که یک هفته هر
        | روز سر می‌زد هفت نفر حساب می‌شد - و هر کسی با یک حساب می‌توانست
        | عدد را باد کند.
        |
        | ردیف‌های قدیمی (پیش از اینکه دیدن شماره ورود بخواهد) کاربر ندارند
        | و فقط هش IP دارند؛ آنها با هش شمرده می‌شوند تا بیرون نیفتند.
        |
        | ⚠️ دو شمارشِ جدا، نه COALESCE(CAST(user_id AS CHAR), ip_hash).
        | آن یکی روی SQLiteِ تست سبز بود، ولی در MySQL رشته‌ی ساخته‌شده از
        | CAST با collationِ اتصال می‌آید و ip_hash با collationِ جدول؛
        | اگر این دو یکی نباشند، کل صفحه با «Illegal mix of collations»
        | می‌خوابد. اینجا هر شمارش فقط یک ستون را می‌بیند.
        |
        | صاحب آگهی از قبل شمرده نمی‌شود (AdContactController::record).
        |
        | همه‌ی آگهی‌های فروشنده، نه فقط فعال‌ها: این عدد درباره‌ی خودِ
        | فروشنده است، و آگهیِ مکث‌شده یا تمام‌شده چیزی از آن کم نمی‌کند.
        */
        $viewers = AdContactReveal::query()
            ->whereIn('ad_id', Ad::where('user_id', $user->id)->select('id'))
            ->selectRaw('COUNT(DISTINCT user_id) as members')
            ->selectRaw('COUNT(DISTINCT CASE WHEN user_id IS NULL THEN ip_hash END) as guests')
            ->first();

        $contactViewers = (int) $viewers->members + (int) $viewers->guests;

        return view('front.seller-profile', compact('user','products','services','reviews','averageRating','reviewCount','salesCount','contactViewers'));
    }
}
