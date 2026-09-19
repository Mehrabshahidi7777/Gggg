<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| درخواست ویرایش آگهی
|--------------------------------------------------------------------------
|
| تا امروز ارائه‌دهنده پس از ثبت آگهی هیچ راهی برای اصلاح آن نداشت؛
| یک شماره‌ی اشتباه یا قیمت قدیمی فقط با تماس با پشتیبانی درست
| می‌شد.
|
| حالا می‌تواند ویرایش کند، اما تغییر بلافاصله روی سایت نمی‌نشیند:
| اینجا به‌صورت «درخواست» ذخیره می‌شود و تا وقتی ادمین تأییدش نکند
| آگهیِ منتشرشده دست نمی‌خورد. بدون این واسطه، هر کسی می‌توانست
| آگهی بی‌ضرری را تأیید بگیرد و بعد محتوایش را عوض کند.
|
| `payload` فقط فیلدهایی را نگه می‌دارد که واقعاً عوض شده‌اند و
| `original` مقدار آن فیلدها را در لحظه‌ی ثبت درخواست. داشتن هر دو
| باعث می‌شود پنل ادمین بتواند دقیقاً «قبل ← بعد» را نشان دهد، حتی
| اگر بین ثبت و بررسی، خودِ ادمین چیزی را تغییر داده باشد.
|
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ad_edits', function (Blueprint $table) {

            $table->id();

            $table->foreignId('ad_id')
                ->constrained('ads')
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            // فیلدهای تغییرکرده: {field: مقدار جدید}
            $table->json('payload');

            // همان فیلدها با مقدار قدیمی، برای نمایش تفاوت
            $table->json('original');

            /*
            | تصاویری که کاربر در این ویرایش اضافه کرده. فایل‌ها همان
            | موقع آپلود می‌شوند ولی تا تأیید، رکورد ad_images ساخته
            | نمی‌شود، پس روی سایت دیده نمی‌شوند.
            */
            $table->json('added_images')->nullable();

            // شناسه‌ی ad_images هایی که کاربر خواسته حذف شوند
            $table->json('removed_image_ids')->nullable();

            $table->enum('status', ['pending', 'approved', 'rejected'])
                ->default('pending');

            $table->text('rejection_reason')->nullable();

            $table->foreignId('reviewed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('reviewed_at')->nullable();

            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index(['ad_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ad_edits');
    }
};
