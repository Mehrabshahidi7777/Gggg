<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\Front\HomeController;
use App\Http\Controllers\Front\AdController;
use App\Http\Controllers\Front\AdSubmitController;
use App\Http\Controllers\Front\PageController;
use App\Http\Controllers\ServiceSubscriptionController;
use App\Http\Controllers\ProductSubscriptionController;
use App\Http\Controllers\Front\CartController;
use App\Http\Controllers\Front\OrderController;
use App\Http\Controllers\Front\SellerPanelController;
use App\Http\Controllers\Front\SellerProfileController;
use App\Http\Controllers\Front\ReviewController;
use App\Http\Controllers\Front\AdRatingController;
use App\Http\Controllers\Front\AdContactController;
use App\Http\Controllers\Front\SitemapController;
use App\Http\Controllers\Front\AdEditController;
use App\Http\Controllers\Front\ActivityController;
use App\Http\Controllers\Front\FeaturedController;
use App\Http\Controllers\Front\AdVisibilityController;
use App\Http\Controllers\Front\MobileAttachController;


/*
|--------------------------------------------------------------------------
| Home
|--------------------------------------------------------------------------
*/

Route::get('/', [HomeController::class, 'index'])
    ->name('home');

/*
|--------------------------------------------------------------------------
| Ads
|--------------------------------------------------------------------------
*/

Route::get('/products', [AdController::class, 'products'])
    ->name('products');

Route::get('/services', [AdController::class, 'services'])
    ->name('services');

Route::get('/categories', [PageController::class, 'categories'])
    ->name('categories.index');

Route::get('/search', [AdController::class, 'search'])
    ->name('search');

Route::get('/ad/{slug}', [AdController::class, 'show'])
    ->name('ad.show');

Route::get('/seller/{user}', [SellerProfileController::class, 'show'])
    ->name('seller.profile');

/*
|--------------------------------------------------------------------------
| Ad Rating
|--------------------------------------------------------------------------
|
| امتیاز ستاره‌ای عمومی. هم از کارت‌های فهرست (AJAX) و هم از صفحه‌ی
| خود آگهی (ارسال معمولی فرم) به همین مسیر می‌آید. throttle جلوی
| اسکریپت‌نویسی برای بالا/پایین بردن سریع امتیازها را می‌گیرد.
|
*/
Route::post('/ad/{ad}/rate', [AdRatingController::class, 'store'])
    ->middleware(['auth', 'throttle:30,1'])
    ->name('ad.rate');

/*
|--------------------------------------------------------------------------
| Contact Reveal
|--------------------------------------------------------------------------
|
| شماره تماس دیگر داخل HTML صفحه نیست و فقط از این مسیر برگردانده
| می‌شود. throttle عمداً سفت است (۲۰ درخواست در دقیقه برای هر IP):
| یک کاربر واقعی در دقیقه نهایتاً چند آگهی را باز می‌کند، اما رباتی
| که می‌خواهد کل شماره‌های سایت را جمع کند خیلی زود متوقف می‌شود.
|
*/
Route::post('/ad/{ad}/contact', [AdContactController::class, 'show'])
    ->middleware('throttle:20,1')
    ->name('ad.contact');

/*
|--------------------------------------------------------------------------
| Pages
|--------------------------------------------------------------------------
*/

Route::get('/sitemap.xml', [SitemapController::class, 'index'])
    ->name('sitemap');

Route::get('/about', [PageController::class, 'about'])
    ->name('about');

/*
| همه‌ی آگهی‌های ویژه.
|
| صفحه‌ی اصلی سقف دارد و هر ساعت می‌چرخد؛ اینجا همه‌شان هستند.
*/
Route::get('/featured', [FeaturedController::class, 'index'])
    ->name('featured');

Route::get('/contact', [PageController::class, 'contact'])
    ->name('contact');

Route::post('/contact', [PageController::class, 'contactStore'])
    ->name('contact.store');


/*
|--------------------------------------------------------------------------
| API
|--------------------------------------------------------------------------
*/

Route::get('/api/cities/{province}', [AdController::class, 'getCities'])
    ->name('cities');


/*
|--------------------------------------------------------------------------
| Cart
|--------------------------------------------------------------------------
*/

/*
| سبد خرید فقط وقتی در دسترس است که خرید آنلاین روشن باشد
| (config/marketplace.php). در حالت فعلی، آگهی محصول هم مثل خدمت
| «تماس مستقیم» است و این مسیرها غیرفعال‌اند.
*/
Route::middleware('checkout.enabled')->group(function () {

    Route::get('/cart', [CartController::class, 'index'])
        ->name('cart.index');

    Route::post('/cart/add/{ad}', [CartController::class, 'add'])
        ->name('cart.add');

    Route::patch('/cart/{ad}', [CartController::class, 'update'])
        ->name('cart.update');

    Route::delete('/cart/{ad}', [CartController::class, 'remove'])
        ->name('cart.remove');
});


/*
|--------------------------------------------------------------------------
| Guest Authentication
|--------------------------------------------------------------------------
*/

Route::middleware('guest')->group(function () {

    Route::get('/login', [AuthController::class, 'showLogin'])
        ->name('login');

    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:6,1')
        ->name('login.store');

    Route::get('/register', [AuthController::class, 'showRegister'])
        ->name('register');

    Route::post('/register', [AuthController::class, 'register'])
        ->name('register.store');

    Route::post('/login/mobile', [AuthController::class, 'requestMobileOtp'])
        ->name('login.mobile.request');

    Route::post('/register/mobile', [AuthController::class, 'requestMobileRegistrationOtp'])
        ->name('register.mobile.request');

    Route::get('/login/mobile/verify', [AuthController::class, 'showMobileVerify'])
        ->name('mobile.verify');

    Route::post('/login/mobile/verify', [AuthController::class, 'verifyMobileOtp'])
        ->name('mobile.verify.store');
});


/*
|--------------------------------------------------------------------------
| Logout
|--------------------------------------------------------------------------
*/

Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');


/*
|--------------------------------------------------------------------------
| SEP Payment Callbacks
|--------------------------------------------------------------------------
|
| درگاه SEP بعد از پرداخت اطلاعات تراکنش را با POST ارسال می‌کند.
| GET نیز برای سازگاری با حالت‌های دیگر نگه داشته شده است.
|
*/

Route::match(
    ['GET', 'POST'],
    '/payments/sep/order',
    [OrderController::class, 'callback']
)->name('order.payment.callback');

Route::match(
    ['GET', 'POST'],
    '/payments/sep/service',
    [ServiceSubscriptionController::class, 'callback']
)->name('service.payment.callback');

Route::match(
    ['GET', 'POST'],
    '/payments/sep/product',
    [ProductSubscriptionController::class, 'callback']
)->name('product.payment.callback');

/*
|--------------------------------------------------------------------------
| Authenticated Routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {

    /*
    | Ad Submission
    */

    /*
    | ⚠️ require.mobile
    |
    | حسابی که با ایمیل ساخته شده هیچ شماره‌ای ندارد و پروفایل هم
    | جایی برای افزودنش نداشت. نتیجه‌اش این بود که یادآوری تمدید
    | اشتراک بی‌صدا رد می‌شد و آگهی‌هایش بی‌خبر تعلیق می‌شد.
    |
    | حالا قبل از اولین آگهی، شماره گرفته و تأیید می‌شود. کسی که با
    | موبایل ثبت‌نام کرده اصلاً متوجه این مرحله نمی‌شود.
    */

    Route::get('/submit-ad', [AdSubmitController::class, 'create'])
        ->middleware('require.mobile')
        ->name('ad.create');

    Route::post('/submit-ad', [AdSubmitController::class, 'store'])
        ->middleware('require.mobile')
        ->name('ad.store');


    /*
    | افزودن و تأیید شماره موبایل. توضیح کامل در MobileAttachController.
    */

    Route::get('/verify-mobile', [MobileAttachController::class, 'show'])
        ->name('mobile.attach');

    Route::post('/verify-mobile', [MobileAttachController::class, 'request'])
        ->middleware('throttle:10,1')
        ->name('mobile.attach.request');

    Route::post('/verify-mobile/confirm', [MobileAttachController::class, 'verify'])
        ->middleware('throttle:20,1')
        ->name('mobile.attach.verify');


    /*
    | Ad Editing
    |
    | ارائه‌دهنده آگهی خودش را ویرایش می‌کند، اما تغییر بلافاصله روی
    | سایت نمی‌نشیند: به‌صورت درخواست ثبت می‌شود و منتظر تأیید مدیر
    | می‌ماند.
    */

    Route::get('/my-ads/{ad}/edit', [AdEditController::class, 'edit'])
        ->name('ad.edit');

    Route::put('/my-ads/{ad}', [AdEditController::class, 'update'])
        ->name('ad.edit.store');

    Route::delete('/my-ads/{ad}/edit', [AdEditController::class, 'cancel'])
        ->name('ad.edit.cancel');


    /*
    | خاموش و روشن کردن موقتِ آگهی.
    |
    | جدا از تعلیقِ سیستمی (is_suspended) که زمان‌بند با پایان اشتراک
    | می‌گذارد. توضیح کامل در AdVisibilityController.
    */

    Route::post('/my-ads/{ad}/pause', [AdVisibilityController::class, 'pause'])
        ->name('ad.pause');

    Route::post('/my-ads/{ad}/resume', [AdVisibilityController::class, 'resume'])
        ->name('ad.resume');


    /*
    | Profile
    */

    Route::get('/profile', [PageController::class, 'profile'])
        ->name('profile');

    Route::put('/profile', [PageController::class, 'updateProfile'])
        ->name('profile.update');

    Route::put('/profile/password', [PageController::class, 'updatePassword'])
        ->name('profile.password.update');


    /*
    | Activity
    |
    | پنل کاربر. جای «پنل مشتری» را گرفت، که چون خرید آنلاین خاموش
    | است همیشه سه صفر نشان می‌داد. توضیح کامل در ActivityController.
    */

    Route::get('/my-activity', [ActivityController::class, 'index'])
        ->name('activity');


    /*
    | Orders
    |
    | تاریخچه‌ی سفارش‌های آنلاینِ قبلی. از منو فقط به کسی نشان داده
    | می‌شود که واقعاً سفارشی دارد.
    */

    Route::get('/my-orders', [OrderController::class, 'index'])
        ->name('orders.index');

    Route::get('/my-orders/{order}', [OrderController::class, 'show'])
        ->name('orders.show');


    /*
    | Checkout
    */

    Route::post('/my-orders/{order}/items/{item}/review', [ReviewController::class, 'store'])
        ->name('orders.items.review');

    /*
    | تسویه‌حساب هم مثل سبد خرید، تابع همان کلید است. تاریخچه‌ی
    | سفارش‌های قبلی (بالاتر) عمداً بیرون از این محافظ مانده تا همیشه
    | قابل مشاهده باشد.
    */
    Route::middleware('checkout.enabled')->group(function () {

        Route::get('/checkout', [OrderController::class, 'checkout'])
            ->name('checkout');

        Route::post('/checkout', [OrderController::class, 'place'])
            ->name('checkout.place');
    });


    /*
    | Service Subscription
    */

    Route::get('/service-plans', [ServiceSubscriptionController::class, 'plans'])
        ->name('service.plans');

    Route::post('/service-plans/{plan}/pay', [ServiceSubscriptionController::class, 'pay'])
        ->name('service.plans.pay');

    Route::get('/service-panel', [ServiceSubscriptionController::class, 'panel'])
    ->name('service.panel');


    /*
    | Product Subscription
    */

    Route::get('/product-plans', [ProductSubscriptionController::class, 'plans'])
        ->name('product.plans');

    Route::post('/product-plans/{plan}/pay', [ProductSubscriptionController::class, 'pay'])
        ->name('product.plans.pay');

    Route::get('/product-panel', [ProductSubscriptionController::class, 'panel'])
        ->name('product.panel');

    /*
    | Seller Panel
    */

    Route::get('/seller-panel', [SellerPanelController::class, 'index'])
        ->name('seller.panel');

    Route::patch('/seller/orders/{item}', [SellerPanelController::class, 'updateOrderItem'])
        ->name('seller.orders.update');
});


/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
*/

require __DIR__ . '/admin.php';