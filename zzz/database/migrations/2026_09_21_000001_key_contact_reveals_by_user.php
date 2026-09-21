<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| کلید یکتای نمایش شماره: از IP به حساب کاربری
|--------------------------------------------------------------------------
|
| تا پیش از این، مهمان‌ها هم می‌توانستند شماره ببینند، پس تنها چیزی که
| برای جلوگیری از شمارش تکراری در دست بود هشِ IP بود:
|
|     unique (ad_id, ip_hash, revealed_on)
|
| حالا که دیدن شماره نیاز به حساب دارد، آن کلید دو جای واقعی اشتباه
| می‌کند:
|
| ۱. دو نفر پشت یک IP (خانه، دفتر، اینترنت موبایل) اگر در یک روز یک
|    آگهی را ببینند، فقط نفر اول ثبت می‌شود. نفر دوم در پنل خودش
|    چیزی نمی‌بیند - در حالی که واقعاً شماره را دیده.
|
| ۲. یک نفر که وسط روز از وای‌فای به دیتای موبایل می‌رود، دو ردیف
|    می‌سازد و در پنلش تکراری می‌بیند.
|
| کلید درست حالا «این کاربر، این آگهی، امروز» است.
|
| ip_hash حذف نمی‌شود؛ همچنان ثبت می‌شود و برای بررسی سوءاستفاده به
| درد می‌خورد، فقط دیگر کلید یکتا نیست.
|
| ردیف‌های قدیمیِ مهمان user_id تهی دارند. MySQL چند NULL را در کلید
| یکتا می‌پذیرد، پس آنها دست‌نخورده می‌مانند و تداخلی نمی‌سازند.
|
*/
return new class extends Migration
{
    private const OLD_INDEX = 'ad_contact_reveals_daily_unique';
    private const NEW_INDEX = 'ad_contact_reveals_user_daily_unique';

    public function up(): void
    {
        Schema::table('ad_contact_reveals', function (Blueprint $table) {

            if ($this->indexExists(self::OLD_INDEX)) {
                $table->dropUnique(self::OLD_INDEX);
            }

            if (! $this->indexExists(self::NEW_INDEX)) {
                $table->unique(['ad_id', 'user_id', 'revealed_on'], self::NEW_INDEX);
            }

            if (! $this->indexExists('ad_contact_reveals_ip_hash_index')) {
                $table->index('ip_hash');
            }
        });
    }

    public function down(): void
    {
        Schema::table('ad_contact_reveals', function (Blueprint $table) {

            if ($this->indexExists(self::NEW_INDEX)) {
                $table->dropUnique(self::NEW_INDEX);
            }

            if (! $this->indexExists(self::OLD_INDEX)) {
                $table->unique(['ad_id', 'ip_hash', 'revealed_on'], self::OLD_INDEX);
            }
        });
    }

    /*
    | نام ایندکس در MySQL و SQLite جاهای متفاوتی نگهداری می‌شود و
    | Doctrine در پروژه نصب نیست، پس مستقیم از خود درایور پرسیده
    | می‌شود.
    */
    private function indexExists(string $name): bool
    {
        $connection = Schema::getConnection();

        if ($connection->getDriverName() === 'sqlite') {
            return (bool) $connection->selectOne(
                "SELECT name FROM sqlite_master WHERE type = 'index' AND name = ?",
                [$name]
            );
        }

        return (bool) $connection->selectOne(
            'SELECT INDEX_NAME FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = ?
               AND INDEX_NAME = ?
             LIMIT 1',
            ['ad_contact_reveals', $name]
        );
    }
};
