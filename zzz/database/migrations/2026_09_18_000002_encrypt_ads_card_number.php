<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| رمزنگاری شماره شبا (card_number) در جدول ads
|--------------------------------------------------------------------------
|
| این ستون تا الان به‌صورت متن ساده (plaintext) ذخیره می‌شد. این migration
| دو کار می‌کند:
|
| ۱. ستون را از varchar(24) به text تبدیل می‌کند - چون مقدار رمزنگاری‌شده
|    (خروجی Laravel Encrypter) خیلی طولانی‌تر از ۲۴ کاراکتر اصلی است و در
|    varchar(24) اصلاً جا نمی‌شود.
|
| ۲. تک‌تک مقادیر فعلی (که هنوز متن ساده هستند) را با Crypt::encryptString
|    رمزنگاری می‌کند - دقیقاً با استفاده مستقیم از DB Query Builder
|    (نه از طریق مدل Ad)، چون این کار قبل از اضافه‌شدن cast=>'encrypted'
|    به مدل انجام می‌شود.
|
| نکته‌ی مهم برای دیپلوی: این migration باید همراه با تغییر مدل
| app/Models/Ad.php (اضافه‌شدن 'card_number' => 'encrypted' به casts)
| با هم دیپلوی شوند. اگر فقط migration اجرا شود ولی مدل هنوز آپدیت
| نشده باشد، خواندن card_number از طریق مدل Ad مقدار رمزنگاری‌شده‌ی
| خام را برمی‌گرداند (نه شماره‌ی واقعی) تا زمانی که مدل هم آپدیت شود.
| برعکسش (مدل زودتر آپدیت شود) باعث خطای «قابل رمزگشایی نیست» در
| خواندن مقادیر قدیمی می‌شود. پس این دو فایل را با هم روی سرور بگذارید.
|
*/

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ads', function (Blueprint $table) {
            $table->text('card_number')->nullable()->change();
        });

        DB::table('ads')
            ->whereNotNull('card_number')
            ->where('card_number', '!=', '')
            ->orderBy('id')
            ->chunkById(200, function ($ads) {
                foreach ($ads as $ad) {
                    // اگر این migration دوباره اجرا شود (مثلاً به اشتباه)،
                    // یک مقدار از قبل رمزنگاری‌شده را دوباره رمزنگاری نکند.
                    if (self::looksAlreadyEncrypted($ad->card_number)) {
                        continue;
                    }

                    DB::table('ads')
                        ->where('id', $ad->id)
                        ->update([
                            'card_number' => Crypt::encryptString($ad->card_number),
                        ]);
                }
            });
    }

    public function down(): void
    {
        DB::table('ads')
            ->whereNotNull('card_number')
            ->orderBy('id')
            ->chunkById(200, function ($ads) {
                foreach ($ads as $ad) {
                    try {
                        $plain = Crypt::decryptString($ad->card_number);
                    } catch (\Throwable $e) {
                        // اگر از قبل رمزگشایی‌نشدنی بود (مثلاً دستی دست‌کاری
                        // شده)، به‌جای کرش‌کردن migration، مقدار را خالی کن.
                        $plain = null;
                    }

                    DB::table('ads')
                        ->where('id', $ad->id)
                        ->update(['card_number' => $plain]);
                }
            });

        Schema::table('ads', function (Blueprint $table) {
            $table->string('card_number', 24)->nullable()->change();
        });
    }

    private static function looksAlreadyEncrypted(string $value): bool
    {
        // خروجی Laravel Encrypter همیشه یک رشته‌ی base64 از یک JSON با
        // کلیدهای iv/value/mac است. یک شماره‌ی شبای ۲۴ رقمی هیچ‌وقت این
        // شکل را ندارد، پس این یک تشخیص ساده و کافی است.
        $decoded = base64_decode($value, true);
        if ($decoded === false) {
            return false;
        }

        $json = json_decode($decoded, true);

        return is_array($json)
            && array_key_exists('iv', $json)
            && array_key_exists('value', $json)
            && array_key_exists('mac', $json);
    }
};
