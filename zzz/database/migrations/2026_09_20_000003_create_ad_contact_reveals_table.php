<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| ثبت «نمایش شماره تماس»
|--------------------------------------------------------------------------
|
| دو مسئله را هم‌زمان حل می‌کند:
|
| ۱. ضد اسکرپ: شماره دیگر داخل HTML صفحه نیست و فقط با یک درخواست
|    جداگانه و throttle‌شده برگردانده می‌شود. ربات دیگر نمی‌تواند با
|    یک خزش ساده کل شماره‌های ارائه‌دهندگان را جمع کند.
|
| ۲. سنجش ارزش اشتراک: ارائه‌دهنده ۱۵۰ هزار تومان می‌دهد و تا امروز
|    هیچ عددی جز بازدید نمی‌دید. حالا پنلش می‌گوید «این ماه ۴۷ نفر
|    شماره‌ی شما را دیدند» - همان عددی که باعث تمدید می‌شود.
|
| برای حریم خصوصی، IP خام ذخیره نمی‌شود؛ فقط هش آن با APP_KEY نگه
| داشته می‌شود که صرفاً برای جلوگیری از شمارش تکراری کافی است و
| قابل بازگشت به IP اصلی نیست.
|
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ad_contact_reveals', function (Blueprint $table) {

            $table->id();

            $table->foreignId('ad_id')
                ->constrained('ads')
                ->cascadeOnDelete();

            // مهمان‌ها هم می‌توانند شماره ببینند، پس nullable است.
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->char('ip_hash', 64)->nullable();

            $table->timestamp('created_at')->nullable();

            /*
            | کوئری پنل: «تعداد نمایش شماره برای این آگهی در این ماه».
            */
            $table->index(['ad_id', 'created_at']);

            /*
            | جلوگیری از شمارش تکراری: یک بازدیدکننده در یک روز برای یک
            | آگهی فقط یک بار شمرده می‌شود. کلید یکتا شامل تاریخ است تا
            | همان فرد فردا دوباره شمرده شود.
            */
            $table->date('revealed_on');
            $table->unique(['ad_id', 'ip_hash', 'revealed_on'], 'ad_contact_reveals_daily_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ad_contact_reveals');
    }
};
