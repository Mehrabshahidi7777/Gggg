<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Ad;
use App\Models\Category;
use Illuminate\Support\Facades\Cache;

class SitemapController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | نقشه‌ی سایت
    |--------------------------------------------------------------------------
    |
    | تا الان سایت هیچ sitemap نداشت. برای یک پلتفرم آگهی این یعنی
    | گوگل فقط صفحه‌هایی را پیدا می‌کند که از جایی لینک خورده‌اند؛
    | آگهی‌هایی که از صفحه‌ی اول جا مانده‌اند عملاً نامرئی می‌مانند.
    |
    | خروجی یک ساعت کش می‌شود. ربات‌های خزنده ممکن است این آدرس را
    | مکرر بزنند و بدون کش، هر بار کل جدول آگهی‌ها خوانده می‌شد.
    |
    */
    public function index()
    {
        $xml = Cache::remember('sitemap.xml', now()->addHour(), function () {

            $urls = [];

            /*
            | صفحه‌های ثابت
            */
            $urls[] = ['loc' => route('home'), 'priority' => '1.0', 'freq' => 'daily'];
            $urls[] = ['loc' => route('products'), 'priority' => '0.9', 'freq' => 'daily'];
            $urls[] = ['loc' => route('services'), 'priority' => '0.9', 'freq' => 'daily'];
            $urls[] = ['loc' => route('categories.index'), 'priority' => '0.7', 'freq' => 'weekly'];
            $urls[] = ['loc' => route('about'), 'priority' => '0.4', 'freq' => 'monthly'];
            $urls[] = ['loc' => route('contact'), 'priority' => '0.4', 'freq' => 'monthly'];

            /*
            | صفحه‌ی هر دسته‌بندی، با فیلتر همان دسته
            */
            Category::where('is_active', true)
                ->get(['id', 'type'])
                ->each(function ($category) use (&$urls) {
                    $route = $category->type === 'product' ? 'products' : 'services';
                    $urls[] = [
                        'loc' => route($route, ['category' => $category->id]),
                        'priority' => '0.6',
                        'freq' => 'weekly',
                    ];
                });

            /*
            | آگهی‌ها - فقط آنهایی که واقعاً برای عموم قابل دیدن‌اند.
            | فرستادن آگهی تعلیق‌شده یا منقضی به گوگل یعنی تحویل ۴۰۴
            | که به اعتبار دامنه ضربه می‌زند.
            */
            Ad::approved()
                ->select(['slug', 'updated_at'])
                ->orderByDesc('updated_at')
                ->chunk(500, function ($ads) use (&$urls) {
                    foreach ($ads as $ad) {
                        $urls[] = [
                            'loc' => route('ad.show', $ad->slug),
                            'lastmod' => $ad->updated_at?->toAtomString(),
                            'priority' => '0.8',
                            'freq' => 'weekly',
                        ];
                    }
                });

            return $this->render($urls);
        });

        return response($xml, 200)
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }

    private function render(array $urls): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        foreach ($urls as $url) {

            $xml .= "  <url>\n";
            $xml .= '    <loc>' . htmlspecialchars($url['loc'], ENT_XML1) . "</loc>\n";

            if (! empty($url['lastmod'])) {
                $xml .= '    <lastmod>' . $url['lastmod'] . "</lastmod>\n";
            }

            $xml .= '    <changefreq>' . $url['freq'] . "</changefreq>\n";
            $xml .= '    <priority>' . $url['priority'] . "</priority>\n";
            $xml .= "  </url>\n";
        }

        return $xml . '</urlset>';
    }
}
