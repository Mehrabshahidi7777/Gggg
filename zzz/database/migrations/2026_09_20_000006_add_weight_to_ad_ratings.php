<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| وزن هر امتیاز
|--------------------------------------------------------------------------
|
| وزن در لحظه‌ی ثبت امتیاز حساب و ذخیره می‌شود، نه هنگام خواندن. دلیلش
| این است که میانگین وزنی باید با یک SUM ساده در دیتابیس درآید؛ اگر
| وزن را موقع خواندن حساب می‌کردیم، هر کارت آگهی باید سن حساب همه‌ی
| امتیازدهنده‌ها را می‌خواند.
|
| مقدار پیش‌فرض ۱.۰۰ است تا امتیازهایی که پیش از این تغییر ثبت شده‌اند
| عقب‌گرد جریمه نشوند - آنها زیر قانون دیگری ثبت شده بودند.
|
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ad_ratings', function (Blueprint $table) {
            $table->decimal('weight', 3, 2)->default(1.00)->after('rating');
        });
    }

    public function down(): void
    {
        Schema::table('ad_ratings', function (Blueprint $table) {
            $table->dropColumn('weight');
        });
    }
};
