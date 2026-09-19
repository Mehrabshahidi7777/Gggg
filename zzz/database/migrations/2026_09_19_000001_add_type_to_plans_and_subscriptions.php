<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| اشتراک محصولات - استفاده از همون جدول‌های خدمات
|--------------------------------------------------------------------------
|
| به‌جای ساختن دو جدول کاملاً جدید (product_plans/product_subscriptions)
| که کپی یک‌به‌یکِ service_plans/service_subscriptions می‌شدند، همون دو
| جدول موجود رو با یک ستون type گسترش می‌دیم. دلیلش: منطق این دو جدول
| (زنجیره‌ی تمدید، مهلت ۶ ماهه، وضعیت‌ها) خیلی ظریف و تست‌شده‌ست؛
| کپی‌کردنش یعنی هر باگی که در آینده پیدا/رفع بشه باید دوجا رفع بشه.
|
| ستون‌های service_plan_id / serviceSubscriptions در کد همون اسم قبلی
| رو حفظ می‌کنن (فقط برای پرهیز از یک rename پرخطر روی دیتابیس زنده)،
| ولی از این به بعد می‌تونن هم پلن/اشتراک «خدمت» نگه دارن هم «محصول» -
| فرقشون فقط همین ستون type است.
|
| ردیف‌های فعلی (که همه مال خدمات هستن) با مقدار پیش‌فرض 'service' پر
| می‌شن، پس هیچ داده‌ی موجودی جابه‌جا/خراب نمی‌شه.
|
*/

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_plans', function (Blueprint $table) {
            $table->enum('type', ['service', 'product'])
                ->default('service')
                ->after('id');
        });

        /*
        | نکته‌ی مهم: ستون months قبلاً به‌تنهایی unique بود (یعنی مثلاً
        | فقط یک پلن «۳ ماهه» در کل جدول مجاز بود). حالا که همین جدول
        | قراره هم پلن خدمت هم پلن محصول نگه داره، این محدودیت اشتباه
        | می‌شود - چون نمی‌گذارد یک پلن «۳ ماهه»‌ی خدمت و یک پلن «۳
        | ماهه»‌ی محصول همزمان وجود داشته باشند. این index قدیمی حذف و
        | با یک unique ترکیبی روی (type, months) جایگزین می‌شود، که هر
        | نوع را جدا از دیگری محدود می‌کند.
        */
        Schema::table('service_plans', function (Blueprint $table) {
            $table->dropUnique(['months']);
            $table->unique(['type', 'months']);
        });

        Schema::table('service_subscriptions', function (Blueprint $table) {
            $table->enum('type', ['service', 'product'])
                ->default('service')
                ->after('id');
        });
    }

    public function down(): void
    {
        Schema::table('service_plans', function (Blueprint $table) {
            $table->dropUnique(['type', 'months']);
            $table->unique(['months']);
        });

        Schema::table('service_plans', function (Blueprint $table) {
            $table->dropColumn('type');
        });

        Schema::table('service_subscriptions', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};
