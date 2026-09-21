<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Ad;

/*
|--------------------------------------------------------------------------
| همه‌ی آگهی‌های ویژه
|--------------------------------------------------------------------------
|
| صفحه‌ی اصلی سقف دارد، چون «ویژه» فقط وقتی ارزش دارد که کمیاب باشد.
| ولی ارائه‌دهنده‌ای که پول داده باید بداند آگهی‌اش جایی هست که همیشه
| دیده می‌شود، نه فقط در نوبت ساعتی‌اش.
|
| اینجا همه‌ی ویژه‌ها هستند، بدون چرخش و بدون سقف - فقط صفحه‌بندی.
|
| مرتب‌سازی عمداً latest است نه چرخشی: این صفحه برای مرور کردن است و
| ترتیبش باید بین صفحه‌ی ۱ و ۲ پایدار بماند، وگرنه کاربر هنگام
| صفحه‌بندی آگهی تکراری می‌بیند و بعضی را اصلاً نمی‌بیند.
|
*/
class FeaturedController extends Controller
{
    private const PER_PAGE = 12;

    public function index()
    {
        return view('front.featured', [
            'products' => $this->page('product', 'products'),
            'services' => $this->page('service', 'services'),
        ]);
    }

    private function page(string $type, string $pageName)
    {
        return Ad::approved()
            ->byType($type)
            ->featured()
            ->with([
                'category',
                'province',
                'city',
                'primaryImage',
            ])
            ->withRatingSummary()
            ->latest()
            ->paginate(self::PER_PAGE, ['*'], $pageName)
            ->withQueryString();
    }
}
