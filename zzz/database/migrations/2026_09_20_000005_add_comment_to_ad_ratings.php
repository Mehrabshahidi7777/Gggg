<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| نظر متنی در کنار امتیاز ستاره‌ای
|--------------------------------------------------------------------------
|
| امتیاز و نظر عمداً در یک ردیف نگه داشته می‌شوند: هر کاربر برای هر
| آگهی یک نظر دارد، دقیقاً مثل یک امتیاز، و قید یکتای موجود روی
| (ad_id, user_id) همان قانون را برای نظر هم اعمال می‌کند.
|
| اما چرخه‌ی تأییدشان متفاوت است:
|
|   امتیاز ستاره → بلافاصله در میانگین اعمال می‌شود
|   نظر متنی     → تا تأیید ادمین روی سایت دیده نمی‌شود
|
| دلیلش ساده است: یک عدد بین ۱ تا ۵ نمی‌تواند توهین یا تبلیغ یا
| شماره تلفن رقیب باشد، ولی متن آزاد می‌تواند.
|
| comment_status وقتی NULL است که کاربر فقط ستاره داده و نظری
| ننوشته - پس صف تأیید ادمین با رکوردهای بی‌متن شلوغ نمی‌شود.
|
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ad_ratings', function (Blueprint $table) {

            $table->text('comment')->nullable()->after('rating');

            $table->enum('comment_status', ['pending', 'approved', 'rejected'])
                ->nullable()
                ->after('comment');

            $table->text('comment_rejection_reason')->nullable()->after('comment_status');

            $table->timestamp('comment_reviewed_at')->nullable()->after('comment_rejection_reason');

            $table->foreignId('comment_reviewed_by')
                ->nullable()
                ->after('comment_reviewed_at')
                ->constrained('users')
                ->nullOnDelete();

            /*
            | صف تأیید ادمین: «نظرهای در انتظار، قدیمی‌ترین اول».
            */
            $table->index(['comment_status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::table('ad_ratings', function (Blueprint $table) {
            $table->dropForeign(['comment_reviewed_by']);
            $table->dropIndex(['comment_status', 'created_at']);
            $table->dropColumn([
                'comment',
                'comment_status',
                'comment_rejection_reason',
                'comment_reviewed_at',
                'comment_reviewed_by',
            ]);
        });
    }
};
