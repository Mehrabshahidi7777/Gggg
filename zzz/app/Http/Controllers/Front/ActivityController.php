<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\AdContactReveal;
use App\Models\AdRating;

/*
|--------------------------------------------------------------------------
| پنل کاربر: فعالیت‌های من
|--------------------------------------------------------------------------
|
| این صفحه جای «پنل مشتری» را گرفت.
|
| پنل قبلی سفارش‌ها و وضعیت خرید را نشان می‌داد، ولی خرید آنلاین در
| سایت خاموش است (marketplace.online_checkout) و تماس مستقیم انجام
| می‌شود. نتیجه‌اش صفحه‌ای بود که همیشه سه صفر نشان می‌داد - یعنی به
| کاربر می‌گفت «اینجا هیچ خبری نیست».
|
| چیزی که کاربرِ این سایت واقعاً انجام می‌دهد سه چیز است: ستاره
| می‌دهد، نظر می‌گذارد، و شماره‌ی ارائه‌دهنده‌ها را می‌بیند. همان‌ها
| اینجا می‌آیند.
|
| «شماره‌هایی که دیده‌ام» کاربردی‌ترینشان است: کسی که هفته‌ی پیش با
| سه فروشنده تماس گرفته، معمولاً یادش نمی‌ماند کدام بودند.
|
*/
class ActivityController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        /*
        | امتیاز و نظر در یک جدول‌اند (ad_ratings)، چون نظر بدون ستاره
        | ثبت نمی‌شود. پس یک کوئری هر دو را می‌آورد.
        |
        | آگهی حذف‌شده: رابطه‌ی ad ممکن است تهی باشد، و ویو برای همین
        | حالت هم متن دارد.
        */
        $ratings = AdRating::query()
            ->where('user_id', $user->id)
            ->with('ad:id,slug,title,type')
            ->latest()
            ->paginate(10, ['*'], 'ratings');

        /*
        | شماره‌هایی که دیده. کلید یکتای جدول «این کاربر، این آگهی،
        | این روز» است، پس هر ردیف یک بار تماس واقعی در یک روز است و
        | نیازی به group by نیست.
        */
        $reveals = AdContactReveal::query()
            ->where('user_id', $user->id)
            ->with('ad:id,slug,title,type,phone')
            ->latest('revealed_on')
            ->latest('id')
            ->paginate(10, ['*'], 'reveals');

        $stats = [
            'ratings' => AdRating::where('user_id', $user->id)->count(),
            'comments' => AdRating::where('user_id', $user->id)->whereNotNull('comment')->count(),
            'reveals' => AdContactReveal::where('user_id', $user->id)->count(),
        ];

        /*
        | سفارش‌های قدیمی فقط برای کسی معنی دارد که واقعاً سفارشی دارد.
        | همان قاعده‌ای که برای «سفارش‌های در جریان» در منو هم هست.
        */
        $hasOrders = $user->orders()->exists();

        return view('front.activity', compact('ratings', 'reveals', 'stats', 'hasOrders'));
    }
}
