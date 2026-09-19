<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| امتیاز ستاره‌ای آگهی‌ها
|--------------------------------------------------------------------------
|
| این جدول عمداً از جدول `reviews` جداست و جایگزین آن نمی‌شود.
|
| `reviews` یک «نظر تأییدشده با خرید» است: به order_item گره خورده،
| فقط بعد از تکمیل سفارش ثبت می‌شود و seller_id دارد. حالا که خرید
| آنلاین از روی آگهی‌ها برداشته شده، آن جدول دیگر رکورد جدیدی
| نمی‌گیرد ولی رکوردهای قدیمی‌اش باید سالم بمانند.
|
| `ad_ratings` امتیاز عمومی است: هر کاربرِ واردشده می‌تواند به هر
| آگهی (محصول یا خدمت) یک بار امتیاز ۱ تا ۵ بدهد و بعداً همان را
| تغییر دهد. قید یکتا روی (ad_id, user_id) تضمین می‌کند یک کاربر
| نتواند با ارسال مکرر، امتیاز یک آگهی را بالا یا پایین ببرد.
|
*/

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ad_ratings', function (Blueprint $table) {

            $table->id();

            $table->foreignId('ad_id')
                ->constrained('ads')
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->unsignedTinyInteger('rating');

            $table->timestamps();

            /*
            | یک امتیاز به ازای هر کاربر برای هر آگهی.
            | ثبت دوباره = ویرایش همان رکورد، نه رکورد جدید.
            */
            $table->unique(['ad_id', 'user_id']);

            /*
            | میانگین و تعداد امتیاز در صفحات لیست (محصولات، خدمات،
            | جست‌وجو، خانه) برای هر کارت خوانده می‌شود؛ این ایندکس
            | همان withAvg/withCount را روی ad_id جمع می‌زند.
            */
            $table->index(['ad_id', 'rating']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ad_ratings');
    }
};
