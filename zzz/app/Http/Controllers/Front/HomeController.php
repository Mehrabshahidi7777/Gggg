<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Ad;
use App\Models\Category;

class HomeController extends Controller
{
    public function index()
    {
        $limit = max(1, (int) config('marketplace.featured_on_home', 8));

        $featuredProducts = $this->featured('product', $limit);
        $featuredServices = $this->featured('service', $limit);

        /*
        | تعداد کل، تا صفحه بداند لینک «دیدن همه» را نشان بدهد یا نه.
        | وقتی همه‌ی ویژه‌ها روی همین صفحه‌اند، آن لینک فقط تکرار است.
        */
        $featuredProductsTotal = Ad::approved()->byType('product')->featured()->count();
        $featuredServicesTotal = Ad::approved()->byType('service')->featured()->count();

        $categories = Category::where('is_active', true)
            ->withCount([
                'ads' => function ($query) {
                    $query->approved();
                },
            ])
            ->orderByDesc('ads_count')
            ->limit(8)
            ->get();

        return view(
            'front.home',
            compact(
                'featuredProducts',
                'featuredServices',
                'featuredProductsTotal',
                'featuredServicesTotal',
                'categories'
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | آگهی‌های ویژه‌ی صفحه‌ی اصلی، با چرخش
    |--------------------------------------------------------------------------
    |
    | تا امروز latest() بود، یعنی همیشه همان تازه‌ترین‌ها. هر که
    | زودتر ویژه می‌شد جایش را می‌گرفت و بقیه هرگز روی صفحه‌ی اصلی
    | دیده نمی‌شدند - در حالی که همه یک پول داده‌اند.
    |
    | حالا نقطه‌ی شروعِ برش هر ساعت جلو می‌رود، پس در طول روز همه
    | نوبت می‌گیرند. چون پنجره به زمان بسته است نه به تصادف، رفرش
    | صفحه چیز تازه‌ای نشان نمی‌دهد و کاربر گیج نمی‌شود.
    |
    | ⚠️ وقتی برش به ته فهرست می‌رسد، باقی‌اش از اول برداشته می‌شود -
    | وگرنه در آن ساعت صفحه‌ی اصلی نصفه می‌ماند.
    |
    | ORDER BY RAND() عمداً استفاده نشد: هم در هر رفرش عوض می‌شد، هم
    | در SQLite (که تست‌ها رویش اجرا می‌شوند) با seed کار نمی‌کند.
    |
    */
    private function featured(string $type, int $limit)
    {
        $base = fn () => Ad::approved()->byType($type)->featured();

        $total = $base()->count();

        if ($total === 0) {
            return collect();
        }

        $window = max(1, (int) config('marketplace.featured_rotation_seconds', 3600));
        /*
        | now() و نه time(): ساعتِ لاراول است، پس Carbon::setTestNow
        | رویش اثر دارد و چرخش قابل تست می‌شود. اولین بار time()
        | نوشتم و تست چرخش هیچ تغییری نمی‌دید، چون آن تابع ساعتِ
        | واقعی سیستم را می‌خواند. در تولید هیچ فرقی نمی‌کند.
        */
        $offset = $total > $limit
            ? (intdiv(now()->getTimestamp(), $window) * $limit) % $total
            : 0;

        $slice = fn (int $skip, int $take) => $base()
            ->with([
                'category',
                'province',
                // کارت آگهی نام شهر را هم چاپ می‌کند؛ بدون این eager
                // load، هر کارت یک کوئری جداگانه برای city می‌زد.
                'city',
                'primaryImage',
            ])
            ->withRatingSummary()
            ->orderBy('id')
            ->skip($skip)
            ->take($take)
            ->get();

        $ads = $slice($offset, $limit);

        $missing = min($limit, $total) - $ads->count();

        if ($missing > 0) {
            $ads = $ads->concat($slice(0, $missing));
        }

        return $ads;
    }
}
