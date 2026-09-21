<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| ایمیل در فرم تماس دیگر اجباری نیست
|--------------------------------------------------------------------------
|
| این سایت بازار مصالح ساختمانی است. خیلی از مخاطبانش ایمیل ندارند یا
| نمی‌خواهند بدهند، ولی همه شماره دارند - و خودِ سایت هم با تماس
| تلفنی کار می‌کند، نه با ایمیل.
|
| پس ستون nullable می‌شود و جایش تلفن اجباری می‌شود، تا همیشه راهی
| برای جواب دادن باشد.
|
| ⚠️ Schema::table برای تغییر nullable بودنِ یک ستون به doctrine/dbal
| نیاز دارد که در این پروژه نصب نیست، پس مستقیم SQL اجرا می‌شود.
| SQLite هم ALTER COLUMN ندارد؛ آنجا چون تست‌ها جدول را از نو
| می‌سازند، کاری لازم نیست.
|
*/
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('contact_messages')) {
            return;
        }

        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement('ALTER TABLE `contact_messages` MODIFY `email` VARCHAR(255) NULL');
    }

    public function down(): void
    {
        if (! Schema::hasTable('contact_messages') || DB::getDriverName() === 'sqlite') {
            return;
        }

        /*
        | برگشت فقط وقتی ممکن است که هیچ ردیفی ایمیل تهی نداشته باشد،
        | وگرنه MySQL آنها را بی‌صدا به رشته‌ی خالی تبدیل می‌کند.
        */
        DB::table('contact_messages')->whereNull('email')->update(['email' => '']);

        DB::statement('ALTER TABLE `contact_messages` MODIFY `email` VARCHAR(255) NOT NULL');
    }
};
