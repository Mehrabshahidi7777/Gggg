<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\ProvinceController;
use App\Http\Controllers\Admin\CityController;
use App\Http\Controllers\Admin\AdController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\SalesController;
use App\Http\Controllers\Admin\ContactMessageController;
use App\Http\Controllers\Admin\PlanController;

Route::prefix(config('admin.prefix'))
    ->middleware(['auth', 'admin'])
    ->name('admin.')
    ->group(function () {

        Route::get('/', [DashboardController::class, 'index'])
            ->name('dashboard');

        Route::resource('categories', CategoryController::class)
            ->except(['show']);

        Route::resource('provinces', ProvinceController::class)
            ->except(['show']);

        Route::resource('cities', CityController::class)
            ->except(['show']);

        Route::resource('ads', AdController::class);

        Route::post('/ads/{ad}/approve', [AdController::class, 'approve'])
            ->name('ads.approve');

        Route::post('/ads/{ad}/reject', [AdController::class, 'reject'])
            ->name('ads.reject');

        Route::post('/ads/{ad}/feature', [AdController::class, 'feature'])
            ->name('ads.feature');

        Route::resource('users', UserController::class);

        Route::resource('plans', PlanController::class)
            ->except(['show']);

        Route::get('/orders', [OrderController::class, 'index'])
            ->name('orders.index');

        Route::get('/sales', [SalesController::class, 'index'])
            ->name('sales.index');

        Route::get('/orders/{order}', [OrderController::class, 'show'])
            ->name('orders.show');

        Route::patch('/orders/{order}', [OrderController::class, 'update'])
            ->name('orders.update');

        Route::get('/settings', [SettingController::class, 'index'])
            ->name('settings');

        Route::put('/settings', [SettingController::class, 'update'])
            ->name('settings.update');


        /*
        |--------------------------------------------------------------------------
        | Contact Messages
        |--------------------------------------------------------------------------
        */

        Route::get('/contact-messages', [ContactMessageController::class, 'index'])
            ->name('contact-messages.index');

        Route::get('/contact-messages/{contactMessage}', [ContactMessageController::class, 'show'])
            ->name('contact-messages.show');

        Route::delete('/contact-messages/{contactMessage}', [ContactMessageController::class, 'destroy'])
            ->name('contact-messages.destroy');
    });