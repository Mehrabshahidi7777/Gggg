<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| اصلاح: backfill با سینتکس مخصوص MySQL جایگزین شد
|--------------------------------------------------------------------------
|
| نسخه‌ی قبلی این فایل از این خط استفاده می‌کرد:
|
|   DB::statement("UPDATE order_items oi JOIN orders o
|                   ON o.id = oi.order_id SET oi.status = o.status");
|
| این سینتکس Multi-table UPDATE مخصوص MySQL است. روی دیتابیس واقعی
| شما (MySQL) درست کار می‌کند و چون این migration از قبل روی سرور
| اجرا شده، این تغییر هیچ اثری روی داده‌های فعلی‌تان ندارد.
|
| مشکل جای دیگری بود: وقتی خواستم برای پروژه تست خودکار (tests/Feature)
| با دیتابیس sqlite در حافظه بنویسم، همین خط باعث خطای سینتکس می‌شد و
| کل مجموعه‌ی migration ها (و در نتیجه همه‌ی تست‌ها) fail می‌کردند - چون
| SQLite این نوع UPDATE JOIN را نمی‌شناسد. همینطور اگر یک‌روز بخواهید
| این پروژه را روی یک دیتابیس دیگر (مثلاً برای محیط توسعه‌ی محلی با
| sqlite) نصب کنید، به همین مشکل می‌خورید.
|
| نسخه‌ی زیر همان کار (کپی‌کردن status هر order روی order_items مربوط
| به آن) را با query builder لاراول انجام می‌دهد که روی MySQL، SQLite و
| Postgres یکسان کار می‌کند.
|
*/

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->enum('status', ['pending','paid','processing','shipped','completed','cancelled'])
                ->default('pending')
                ->after('subtotal');
            $table->index(['order_id', 'status']);
        });

        DB::table('orders')
            ->select('id', 'status')
            ->orderBy('id')
            ->chunkById(200, function ($orders) {
                foreach ($orders as $order) {
                    DB::table('order_items')
                        ->where('order_id', $order->id)
                        ->update(['status' => $order->status]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropIndex(['order_id', 'status']);
            $table->dropColumn('status');
        });
    }
};
