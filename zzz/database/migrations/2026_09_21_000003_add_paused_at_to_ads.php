<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| غیرفعال‌سازی موقتِ آگهی به دست خودِ ارائه‌دهنده
|--------------------------------------------------------------------------
|
| تا امروز تنها راهِ پنهان‌کردن یک آگهی، حذفش بود. کسی که جنسش تمام
| شده یا چند هفته سرش شلوغ است، مجبور بود آگهی را پاک کند و بعد از
| نو بسازد - یعنی عکس‌ها، امتیازها و نظرهایش را برای همیشه از دست
| بدهد.
|
| ⚠️ چرا ستون تازه، و نه همان is_suspended؟
|
| is_suspended مالِ سیستم است: زمان‌بند با پایان اشتراک آن را
| می‌گذارد و با تمدید برمی‌دارد. اگر ارائه‌دهنده هم روی همان بنویسد،
| اولین اجرای کرون تصمیمش را پاک می‌کند - یا بدتر، آگهیِ تعلیق‌شده
| به‌خاطر نپرداختن را «فعال» می‌کند.
|
| پس دو مفهوم جدا، دو ستون جدا:
|
|   is_suspended : سیستم آن را خاموش کرده (اشتراک تمام شده)
|   paused_at    : خودِ صاحب آگهی خاموشش کرده
|
| ایندکس برای همان کوئری‌ای است که هر بازدید از سایت می‌زند
| (scopeApproved).
|
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ads', function (Blueprint $table) {
            if (! Schema::hasColumn('ads', 'paused_at')) {
                $table->timestamp('paused_at')->nullable()->after('suspended_at');
                $table->index('paused_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('ads', function (Blueprint $table) {
            if (Schema::hasColumn('ads', 'paused_at')) {
                $table->dropIndex(['paused_at']);
                $table->dropColumn('paused_at');
            }
        });
    }
};
