<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ServicePlan;
use App\Models\ServiceSubscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| امنیت مسیرهای بازگشت از درگاه
|--------------------------------------------------------------------------
|
| مسیرهای /payments/sep/* عمداً بدون auth و بدون CSRF هستند، چون درگاه
| با POST و بدون نشست مرورگر برمی‌گردد. در عوض تنها مدرک معتبر بودن
| درخواست، مطابقت Token با شناسه‌ی تراکنشِ ذخیره‌شده است.
|
| این تست‌ها تضمین می‌کنند یک درخواست ناشناس - که Token درست ندارد -
| نتواند هیچ رکوردی را تغییر دهد. مخصوصاً نتواند سفارش یا اشتراکِ
| شخص دیگری را «لغو» کند، که قبلاً ممکن بود.
|
*/
class PaymentCallbackTest extends TestCase
{
    use RefreshDatabase;

    private function buyer(): User
    {
        return User::create([
            'name' => 'خریدار',
            'username' => 'buyer',
            'mobile' => '09120000001',
            'password' => 'secret-password',
        ]);
    }

    private function pendingOrder(User $buyer): Order
    {
        $order = Order::create([
            'order_number' => 'SZ-TEST-0001',
            'buyer_id' => $buyer->id,
            'total_amount' => 1000,
            'phone' => '09121234567',
            'address' => 'آدرس',
            'status' => 'pending',
            'payment_transaction_id' => 'real-token-abc123',
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'title' => 'کالا',
            'unit_price' => 1000,
            'quantity' => 1,
            'subtotal' => 1000,
            'status' => 'pending',
        ]);

        return $order;
    }

    /*
    | این دقیقاً همان حمله‌ای است که قبلاً کار می‌کرد: مهاجم فقط شناسه‌ی
    | سفارش را حدس می‌زند و Status=1 می‌فرستد. بدون توکن، سفارش نباید
    | دست بخورد.
    */
    public function test_anonymous_request_cannot_cancel_someone_elses_order(): void
    {
        $order = $this->pendingOrder($this->buyer());

        $this->post('/payments/sep/order', [
            'order' => $order->id,
            'Status' => 1,
        ])->assertRedirect(route('home'));

        $this->assertSame('pending', $order->fresh()->status);
        $this->assertSame('pending', $order->items()->first()->status);
    }

    public function test_wrong_token_cannot_cancel_an_order(): void
    {
        $order = $this->pendingOrder($this->buyer());

        $this->post('/payments/sep/order', [
            'order' => $order->id,
            'Status' => 1,
            'Token' => 'guessed-token-xyz',
        ])->assertRedirect(route('home'));

        $this->assertSame('pending', $order->fresh()->status);
    }

    /*
    | با توکن درست، لغو شدن رفتار درستی است و باید همچنان کار کند.
    */
    public function test_correct_token_with_failed_status_does_cancel(): void
    {
        $order = $this->pendingOrder($this->buyer());

        $this->post('/payments/sep/order', [
            'order' => $order->id,
            'Status' => 1,
            'Token' => 'real-token-abc123',
        ])->assertRedirect(route('orders.show', $order));

        $this->assertSame('cancelled', $order->fresh()->status);
    }

    /*
    | همین محافظت برای اشتراک‌ها هم باید برقرار باشد.
    */
    public function test_anonymous_request_cannot_cancel_a_subscription(): void
    {
        $user = $this->buyer();

        $plan = ServicePlan::create([
            'type' => 'service', 'months' => 1, 'title' => 'یک ماهه',
            'price' => 150000, 'is_active' => true, 'sort_order' => 1,
        ]);

        $subscription = ServiceSubscription::create([
            'type' => 'service',
            'user_id' => $user->id,
            'service_plan_id' => $plan->id,
            'amount' => 150000,
            'status' => 'pending',
            'transaction_id' => 'sub-token-abc',
        ]);

        $this->post('/payments/sep/service', [
            'subscription' => $subscription->id,
            'Status' => 1,
        ])->assertRedirect(route('home'));

        $this->assertSame('pending', $subscription->fresh()->status);
    }

    /*
    | و نباید بتواند کاربر را بدون رمز عبور وارد حساب کند.
    */
    public function test_anonymous_callback_does_not_log_anyone_in(): void
    {
        $order = $this->pendingOrder($this->buyer());

        $this->post('/payments/sep/order', [
            'order' => $order->id,
            'Status' => 2,
            'Token' => 'wrong',
        ]);

        $this->assertGuest();
    }
}
