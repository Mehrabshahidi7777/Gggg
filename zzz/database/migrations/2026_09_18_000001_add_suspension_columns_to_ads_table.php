<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| این migration در پروژه وجود نداشت
|--------------------------------------------------------------------------
|
| ستون‌های is_suspended و suspended_at در دیتابیس واقعی (production) وجود
| دارند و در app/Models/Ad.php هم استفاده می‌شوند (fillable، cast، و در
| ServiceSubscriptionController برای تعلیق خودکار آگهی خدمات بعد از پایان
| اشتراک) اما هیچ migration ای برای ساخت آن‌ها در کد وجود نداشت.
|
| یعنی اگر این پروژه را از صفر روی سرور دیگری نصب کنید یا دستور
| `migrate:fresh` را بزنید، اپلیکیشن روی این دو ستون خطای SQL می‌دهد.
| این migration دقیقاً همان ساختاری را که در دیتابیس فعلی هست بازسازی
| می‌کند تا migration ها با schema واقعی هماهنگ شوند.
|
*/

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ads', function (Blueprint $table) {
            $table->boolean('is_suspended')->default(false)->after('is_featured');
            $table->timestamp('suspended_at')->nullable()->after('is_suspended');

            $table->index(['type', 'is_suspended']);
            $table->index('suspended_at');
        });
    }

    public function down(): void
    {
        Schema::table('ads', function (Blueprint $table) {
            $table->dropIndex(['type', 'is_suspended']);
            $table->dropIndex(['suspended_at']);
            $table->dropColumn(['is_suspended', 'suspended_at']);
        });
    }
};
