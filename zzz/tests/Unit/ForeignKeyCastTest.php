<?php

namespace Tests\Unit;

use App\Models\Ad;
use App\Models\AdContactReveal;
use App\Models\AdEdit;
use App\Models\AdImage;
use App\Models\AdRating;
use App\Models\City;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Review;
use App\Models\ServiceAdDraft;
use App\Models\ServiceSubscription;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| کلیدهای خارجی باید همیشه عدد صحیح خوانده شوند
|--------------------------------------------------------------------------
|
| سراسر کد از مقایسه‌ی سخت‌گیرانه روی شناسه‌ها استفاده می‌شود:
|
|     $ad->user_id === auth()->id()
|
| سمت راست همیشه int است، چون Eloquent کلید اصلی را خودش cast می‌کند.
| سمت چپ یک ستون عادی است و هرچه درایور دیتابیس بدهد همان است. اگر PDO
| با libmysqlclient (به‌جای mysqlnd) بسته شده باشد، همه‌ی ستون‌ها رشته
| برمی‌گردند و '3' === 3 می‌شود false.
|
| آن‌وقت محافظ‌ها بی‌صدا از کار می‌افتند - در هر دو جهت:
|
|   - جایی که شرط «خودی بودن» را رد می‌کند (امتیاز به آگهی خود،
|     شمردن بازدید خود به‌عنوان سرنخ) باز می‌شود،
|   - و جایی که abort_unless گذاشته شده، صاحبِ واقعی ۴۰۳ می‌گیرد.
|
| setRawAttributes دقیقاً همان کاری را می‌کند که چنین درایوری می‌کند:
| مقدار خام رشته‌ای را مستقیم در مدل می‌نشاند، بدون عبور از fillable.
| اگر cast سر جایش باشد، خواندنِ ستون باز هم int است.
|
*/
class ForeignKeyCastTest extends TestCase
{
    /**
     * @param  class-string  $model
     */
    #[DataProvider('foreignKeys')]
    public function test_foreign_key_is_read_as_an_integer(string $model, string $column): void
    {
        $instance = new $model;
        $instance->setRawAttributes([$column => '7']);

        $this->assertSame(
            7,
            $instance->{$column},
            "{$model}::\${$column} باید به‌صورت عدد صحیح خوانده شود."
        );
    }

    public static function foreignKeys(): array
    {
        $keys = [
            Ad::class => ['user_id', 'category_id', 'province_id', 'city_id'],
            AdRating::class => ['ad_id', 'user_id', 'comment_reviewed_by'],
            AdEdit::class => ['ad_id', 'user_id', 'reviewed_by'],
            AdContactReveal::class => ['ad_id', 'user_id'],
            AdImage::class => ['ad_id'],
            Order::class => ['buyer_id'],
            OrderItem::class => ['order_id', 'ad_id', 'seller_id'],
            Review::class => ['order_item_id', 'buyer_id', 'seller_id', 'ad_id'],
            City::class => ['province_id'],
            ServiceSubscription::class => ['user_id', 'service_plan_id'],
            ServiceAdDraft::class => ['user_id'],
        ];

        $cases = [];

        foreach ($keys as $model => $columns) {
            foreach ($columns as $column) {
                $cases[class_basename($model) . '::' . $column] = [$model, $column];
            }
        }

        return $cases;
    }

    /*
    | نقطه‌ی مقابل: شناسه‌های درگاه پرداخت عدد نیستند و نباید cast شوند.
    | اگر روزی کسی از سر عادت این‌ها را هم integer کند، شناسه‌ی تراکنش
    | خراب می‌شود و پیگیری پرداخت از دست می‌رود.
    */
    public function test_payment_identifiers_are_left_alone(): void
    {
        $order = new Order;
        $order->setRawAttributes([
            'payment_reference_id' => 'SEP-0098X',
            'payment_transaction_id' => '00123456789',
        ]);

        $this->assertSame('SEP-0098X', $order->payment_reference_id);
        $this->assertSame('00123456789', $order->payment_transaction_id);

        $subscription = new ServiceSubscription;
        $subscription->setRawAttributes([
            'reference_id' => 'REF-77A',
            'transaction_id' => '00987654321',
        ]);

        $this->assertSame('REF-77A', $subscription->reference_id);
        $this->assertSame('00987654321', $subscription->transaction_id);
    }

    /*
    | و آنچه در عمل مهم است: مقایسه‌ی سخت‌گیرانه‌ای که همه‌ی محافظ‌ها
    | روی آن بنا شده‌اند، با مقدارِ رشته‌ای هم درست کار کند.
    */
    public function test_the_owner_check_holds_even_when_the_driver_returns_strings(): void
    {
        $ad = new Ad;
        $ad->setRawAttributes(['user_id' => '42']);

        $authenticatedUserId = 42;

        $this->assertTrue($ad->user_id === $authenticatedUserId);
    }
}
