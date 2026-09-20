<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Ad;
use App\Models\Category;
use App\Models\Province;
use App\Models\City;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Request;

class AdController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Products
    |--------------------------------------------------------------------------
    */

    public function products(Request $r)
    {
        return $this->listing(
            $r,
            'product',
            'front.products'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Services
    |--------------------------------------------------------------------------
    */

    public function services(Request $r)
    {
        return $this->listing(
            $r,
            'service',
            'front.services'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Smart Search
    |--------------------------------------------------------------------------
    */

    public function search(Request $request)
    {
        $search = trim((string) $request->input('search',''));
        if ($search === '') return redirect()->route('home');

        /*
        |----------------------------------------------------------------------
        | مسیر سریع، با عقب‌نشینی مطمئن
        |----------------------------------------------------------------------
        |
        | scopeSearchFor اگر ایندکس FULLTEXT موجود باشد از آن استفاده
        | می‌کند، وگرنه خودش به LIKE برمی‌گردد.
        |
        | ولی یک تفاوت باقی می‌ماند که باید جدی گرفته شود: FULLTEXT
        | کلمه‌محور است و LIKE زیررشته‌محور. مثلاً جست‌وجوی «یمان»
        | (تکه‌ای از وسط «سیمان») با LIKE نتیجه می‌دهد و با FULLTEXT
        | نه.
        |
        | پس اگر مسیر سریع دست خالی برگشت، همان جست‌وجو یک بار دیگر با
        | LIKE اجرا می‌شود. نتیجه این است که کاربر هیچ‌وقت نتیجه‌ی
        | کمتری از قبل نمی‌گیرد، و در عوض حالت رایج - که کلمه‌ی کامل
        | جست‌وجو می‌شود - هم سریع است و هم بر اساس ارتباط مرتب شده.
        |
        | کوئری دوم فقط وقتی اجرا می‌شود که اولی صفر نتیجه داشته باشد.
        */
        $productColumns = ['title', 'description', 'brand', 'model'];
        $serviceColumns = ['title', 'description', 'full_name', 'service_title'];

        $find = fn (string $type, array $columns, bool $forceLike) => Ad::approved()
            ->where('type', $type)
            ->with(['category','province','city','primaryImage'])
            ->withRatingSummary()
            ->when(
                $forceLike,
                fn ($q) => $q->searchForWithLike($search, $columns),
                fn ($q) => $q->searchFor($search, $columns)
            )
            ->latest()
            ->limit(8)
            ->get();

        $products = $find('product', $productColumns, false);
        $services = $find('service', $serviceColumns, false);

        if ($products->isEmpty() && $services->isEmpty() && Ad::fullTextIsUsable($search)) {
            $products = $find('product', $productColumns, true);
            $services = $find('service', $serviceColumns, true);
        }

        $sellers = User::query()->where('is_admin', false)->where(function($q) use ($search) {
            $q->where('username','like',"%{$search}%")->orWhere('name','like',"%{$search}%")
              ->orWhereHas('ads',fn($a)=>$a->approved()->where('title','like',"%{$search}%"));
        })->withCount(['ads as active_ads_count'=>fn($q)=>$q->approved()])->limit(8)->get();

        $total = $products->count()+$services->count()+$sellers->count();
        return view('front.search', compact('search','products','services','sellers','total'));
    }

    /*
    |--------------------------------------------------------------------------
    | Listing
    |--------------------------------------------------------------------------
    */

    private function listing(
        Request $r,
        string $type,
        string $view
    ) {

        $q = Ad::approved()
            ->byType($type)
            ->with([
                'category',
                'province',
                'city',
                'primaryImage'
            ])
            ->withRatingSummary();


        /*
        | Category
        */

        if ($r->filled('category')) {

            $q->where(
                'category_id',
                $r->category
            );

        }


        /*
        | Province
        */

        if ($r->filled('province')) {

            $q->where(
                'province_id',
                $r->province
            );

        }


        /*
        | City
        */

        if ($r->filled('city')) {

            $q->where(
                'city_id',
                $r->city
            );

        }


        /*
        | Search
        */

        if ($r->filled('search')) {

            $search = trim($r->search);

            $q->where(function ($x) use ($search, $type) {

                $x->where(
                    'title',
                    'like',
                    '%' . $search . '%'
                )

                ->orWhere(
                    'description',
                    'like',
                    '%' . $search . '%'
                )

                ->orWhereHas('category', function ($category) use ($search) {

                    $category->where(
                        'name',
                        'like',
                        '%' . $search . '%'
                    );

                });


                /*
                | فیلدهای اختصاصی محصول
                */

                if ($type === 'product') {

                    $x->orWhere(
                        'brand',
                        'like',
                        '%' . $search . '%'
                    );

                    $x->orWhere(
                        'model',
                        'like',
                        '%' . $search . '%'
                    );
                }


                /*
                | فیلدهای اختصاصی خدمت
                */

                if ($type === 'service') {

                    $x->orWhere(
                        'full_name',
                        'like',
                        '%' . $search . '%'
                    );

                    $x->orWhere(
                        'service_title',
                        'like',
                        '%' . $search . '%'
                    );
                }

            });

        }


        /*
        | Price Filter - Products & Services
        */

        if ($r->filled('min_price')) {

            $q->where(
                'price',
                '>=',
                $r->min_price
            );

        }

        if ($r->filled('max_price')) {

            $q->where(
                'price',
                '<=',
                $r->max_price
            );

        }


        /*
        | Sorting
        */

        $sort = $r->input(
            'sort',
            'latest'
        );

        match ($sort) {

            'price_high' =>
                $q->orderByDesc('price'),

            'price_low' =>
                $q->orderBy('price'),

            'popular' =>
                $q->orderByDesc('views_count'),

            /*
            | بالاترین امتیاز. آگهی بدون امتیاز NULL می‌گیرد و در MySQL
            | با ORDER BY ... DESC اول می‌آید، که برعکسِ خواسته است؛
            | پس اول بر اساس «امتیاز دارد یا نه» مرتب می‌شود و بعد بر
            | اساس میانگین، و در نهایت تعداد امتیاز تا آگهی‌ای که ۵
            | ستاره از یک نفر گرفته بالاتر از ۵ ستاره از ۲۰ نفر نایستد.
            */
            /*
            | میانگین وزنی همان‌جا در ORDER BY حساب می‌شود، چون
            | ratings_weighted_sum و ratings_weight_total ستون‌های
            | مشتق‌شده‌اند و MySQL اجازه نمی‌دهد در همان SELECT به نام
            | مستعارشان در یک عبارت محاسباتی ارجاع داده شود.
            |
            | تقسیم بر صفر برای آگهیِ بدون امتیاز با NULLIF گرفته
            | می‌شود؛ نتیجه NULL است و همان رفتار قبلی را می‌دهد.
            */
            'top_rated' =>
                $q->orderByRaw('(ratings_weighted_sum / NULLIF(ratings_weight_total, 0)) IS NULL')
                  ->orderByRaw('(ratings_weighted_sum / NULLIF(ratings_weight_total, 0)) DESC')
                  ->orderByDesc('ratings_count'),

            default =>
                $q->latest(),
        };


        /*
        | Pagination
        */

        $ads = $q
            ->paginate(12)
            ->withQueryString();


        /*
        | Categories
        */

        $categories = Category::where(
            'type',
            $type
        )
            ->where(
                'is_active',
                true
            )
            ->orderBy('name')
            ->get();


        /*
        | Provinces
        */

        $provinces = Province::with('cities')
            ->orderBy('name')
            ->get();


        return view(
            $view,
            compact(
                'ads',
                'categories',
                'provinces',
                'type'
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Show Ad
    |--------------------------------------------------------------------------
    */

    public function show(string $slug)
    {
        $ad = Ad::approved()
            ->with([
                'user',
                'category',
                'province',
                'city',
                'images',
                'reviews.buyer',
                'approvedComments',
            ])
            ->withRatingSummary()
            ->where(
                'slug',
                $slug
            )
            ->firstOrFail();


        $ad->increment('views_count');


        $similarAds = Ad::approved()
            ->where(
                'category_id',
                $ad->category_id
            )
            ->whereKeyNot(
                $ad->id
            )
            ->with('primaryImage')
            ->withRatingSummary()
            ->latest()
            ->limit(4)
            ->get();


        return view(
            'front.ad-show',
            compact(
                'ad',
                'similarAds'
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Cities
    |--------------------------------------------------------------------------
    */

    public function getCities(int $province)
    {
        $cities = Cache::remember("province:{$province}:cities", now()->addDay(), fn() =>
            City::where('province_id',$province)->orderBy('name')->get(['id','name'])
        );
        return response()->json($cities)->header('Cache-Control','public, max-age=86400');
    }
}