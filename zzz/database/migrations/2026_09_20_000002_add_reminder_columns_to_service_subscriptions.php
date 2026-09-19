<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| ردگیری یادآوری‌های تمدید
|--------------------------------------------------------------------------
|
| کرون هر روز اجرا می‌شود، پس بدون نگه‌داشتن «قبلاً فرستادم یا نه»،
| یک اشتراک که هفت روز تا انقضایش مانده هر روز یک پیامک می‌گرفت -
| هم هزینه‌ی پیامک، هم آزار کاربر.
|
| هر ستون زمانِ ارسالِ همان مرحله را نگه می‌دارد و شرط IS NULL در
| کوئریِ دستور، تضمین می‌کند هر مرحله دقیقاً یک بار ارسال شود.
|
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_subscriptions', function (Blueprint $table) {
            $table->timestamp('reminder_7d_sent_at')->nullable()->after('status');
            $table->timestamp('reminder_1d_sent_at')->nullable()->after('reminder_7d_sent_at');
            $table->timestamp('reminder_expired_sent_at')->nullable()->after('reminder_1d_sent_at');

            /*
            | دستور یادآوری روی «اشتراک‌های فعالی که پایانشان نزدیک
            | است» فیلتر می‌کند؛ این ایندکس همان کوئری را می‌پوشاند.
            */
            $table->index(['status', 'ends_at'], 'service_subscriptions_status_ends_at_index');
        });
    }

    public function down(): void
    {
        Schema::table('service_subscriptions', function (Blueprint $table) {
            $table->dropIndex('service_subscriptions_status_ends_at_index');
            $table->dropColumn([
                'reminder_7d_sent_at',
                'reminder_1d_sent_at',
                'reminder_expired_sent_at',
            ]);
        });
    }
};
