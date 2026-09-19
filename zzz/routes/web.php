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
| Pages
|--------------------------------------------------------------------------
*/

Route::get('/about', [PageController::class, 'about'])
    ->name('about');

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

Route::get('/cart', [CartController::class, 'index'])
    ->name('cart.index');

Route::post('/cart/add/{ad}', [CartController::class, 'add'])
    ->name('cart.add');

Route::patch('/cart/{ad}', [CartController::class, 'update'])
    ->name('cart.update');

Route::delete('/cart/{ad}', [CartController::class, 'remove'])
    ->name('cart.remove');


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

    Route::get('/submit-ad', [AdSubmitController::class, 'create'])
        ->name('ad.create');

    Route::post('/submit-ad', [AdSubmitController::class, 'store'])
        ->name('ad.store');


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
    | Orders
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

    Route::get('/checkout', [OrderController::class, 'checkout'])
        ->name('checkout');

    Route::post('/checkout', [OrderController::class, 'place'])
        ->name('checkout.place');


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