<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Ad;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Shetabit\Multipay\Exceptions\InvalidPaymentException;
use Shetabit\Multipay\Invoice;
use Shetabit\Payment\Facade\Payment;

class OrderController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $orders = $user->orders()->with('items')->latest()->paginate(10);
        $stats = [
            'total' => $user->orders()->count(),
            'active' => $user->orders()->whereIn('status', ['paid', 'processing', 'shipped'])->count(),
            'completed' => $user->orders()->where('status', 'completed')->count(),
            'pending' => $user->orders()->where('status', 'pending')->count(),
        ];

        return view('front.orders.index', compact('orders', 'stats'));
    }

    public function show(Order $order)
    {
        $order = auth()->user()
            ->orders()
            ->with('items.ad', 'items.seller', 'items.review')
            ->findOrFail($order->id);

        return view('front.orders.show', compact('order'));
    }

    public function checkout()
    {
        [$items, $total] = $this->cartData();

        if (!$items) {
            return redirect()
                ->route('cart.index')
                ->with('error', 'سبد خرید شما خالی است.');
        }

        return view('front.checkout', compact('items', 'total'));
    }

    public function place(Request $request)
    {
        [$items, $total] = $this->cartData();

        if (!$items) {
            return redirect()
                ->route('cart.index')
                ->with('error', 'سبد خرید شما خالی است.');
        }

        $data = $request->validate([
            'phone' => 'required|string|max:30',
            'address' => 'required|string|max:1000',
        ]);

        $order = DB::transaction(function () use ($items, $total, $data) {
            $order = Order::create([
                'order_number' => 'SZ-' . now()->format('ymdHis') . '-' . Str::upper(Str::random(5)),
                'buyer_id' => auth()->id(),
                'total_amount' => $total,
                'phone' => $data['phone'],
                'address' => $data['address'],
                'status' => 'pending',
            ]);

            foreach ($items as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'ad_id' => $item['ad']->id,
                    'seller_id' => $item['ad']->user_id,
                    'seller_phone' => $item['ad']->phone,
                    'title' => $item['ad']->title,
                    'unit_price' => $item['ad']->price,
                    'quantity' => $item['quantity'],
                    'subtotal' => $item['subtotal'],
                    'status' => 'pending',
                ]);
            }

            return $order;
        });

        $invoice = (new Invoice)
            ->amount((int) $total)
            ->detail('description', 'خرید محصول در سازمت')
            ->detail('order_id', (string) $order->id)
            ->detail('mobile', $data['phone']);

        try {
            return Payment::via('sep')
                ->callbackUrl(route('order.payment.callback', ['order' => $order->id]))
                ->purchase(
                    $invoice,
                    function ($driver, $transactionId) use ($order) {
                        $order->update(['payment_transaction_id' => $transactionId]);
                    }
                )
                ->pay()
                ->render();
        } catch (\Throwable $e) {
            Log::error('SEP order payment purchase failed', [
                'order_id' => $order->id,
                'amount_toman' => (int) $total,
                'exception' => $e->getMessage(),
            ]);

            $order->update(['status' => 'cancelled']);
            $order->items()->update(['status' => 'cancelled']);

            return back()->with('error', 'اتصال به درگاه پرداخت انجام نشد: ' . $e->getMessage());
        }
    }

    public function callback(Request $request)
    {
        $order = Order::with('items')->findOrFail($request->integer('order'));

        if ($order->status === 'paid') {
            return redirect()
                ->route('orders.show', $order)
                ->with('success', 'این پرداخت قبلاً ثبت شده است.');
        }

        /*
        |--------------------------------------------------------------------------
        | تأیید توکن، قبل از هر تغییری روی سفارش
        |--------------------------------------------------------------------------
        |
        | این آدرس بدون نیاز به ورود در دسترس است و از CSRF هم معاف است
        | (چون درگاه با POST برمی‌گردد و نشست کاربر ممکن است از بین رفته
        | باشد). پس تنها چیزی که ثابت می‌کند این درخواست واقعاً از درگاه
        | برای همین سفارش آمده، مطابقت Token با payment_transaction_id
        | ذخیره‌شده است.
        |
        | ترتیب این بررسی اهمیت دارد: قبلاً بررسی توکن بعد از شاخه‌ی
        | «Status != 2» بود، و آن شاخه سفارش را لغو می‌کرد. یعنی هرکسی
        | بدون ورود و فقط با حدس‌زدن شناسه‌ی سفارش (که عددی ترتیبی است)
        | می‌توانست با یک درخواست ساده به
        |     /payments/sep/order?order=<id>&Status=1
        | سفارشِ در انتظار پرداختِ هر کاربر دیگری را لغو کند.
        |
        | حالا تا وقتی توکن تأیید نشود، هیچ فیلدی از سفارش تغییر
        | نمی‌کند - نه لغو می‌شود و نه پرداخت‌شده. درخواست ناشناس فقط
        | لاگ می‌شود و به خانه برمی‌گردد.
        |
        */
        $token = trim((string) $request->input('Token'));
        $transactionId = trim((string) $order->payment_transaction_id);

        if ($token === '' || $transactionId === '' || $transactionId !== $token) {

            Log::warning('SEP order callback token mismatch', [
                'order_id' => $order->id,
                'stored_transaction_id' => $order->payment_transaction_id,
                'callback_token' => $token,
                'ip' => $request->ip(),
            ]);

            return redirect()
                ->route('home')
                ->with('error', 'اطلاعات تراکنش نامعتبر است.');
        }

        if ((int) $request->input('Status') !== 2) {
            $order->update(['status' => 'cancelled']);
            $order->items()->update(['status' => 'cancelled']);

            return redirect()
                ->route('orders.show', $order)
                ->with('error', 'پرداخت لغو شد یا با موفقیت انجام نشد.');
        }

        try {
            $receipt = Payment::via('sep')
                ->amount((int) $order->total_amount)
                ->transactionId($transactionId)
                ->verify();

            $order->update([
                'status' => 'paid',
                'paid_at' => now(),
                'payment_reference_id' => (string) $receipt->getReferenceId(),
            ]);

            $order->items()->where('status', 'pending')->update(['status' => 'paid']);

            // SEP may return the callback without the previous browser session.
            Auth::loginUsingId($order->buyer_id);
            $request->session()->regenerate();
            session()->forget('cart');

            return redirect()
                ->route('orders.show', $order)
                ->with('success', 'پرداخت با موفقیت انجام شد.');
        } catch (InvalidPaymentException $e) {
            Log::error('SEP order payment verification failed', [
                'order_id' => $order->id,
                'transaction_id' => $transactionId,
                'exception' => $e->getMessage(),
            ]);

            $order->update(['status' => 'cancelled']);
            $order->items()->update(['status' => 'cancelled']);

            return redirect()
                ->route('orders.show', $order)
                ->with('error', 'پرداخت تأیید نشد: ' . $e->getMessage());
        } catch (\Throwable $e) {
            Log::error('SEP order payment completion failed', [
                'order_id' => $order->id,
                'transaction_id' => $transactionId,
                'exception' => $e->getMessage(),
            ]);

            return redirect()
                ->route('orders.show', $order)
                ->with('error', 'خطا در تأیید پرداخت: ' . $e->getMessage());
        }
    }

    private function cartData(): array
    {
        $cart = session('cart', []);

        if (!$cart) return [[], 0];

        $ids = array_keys($cart);

        $ads = Ad::approved()
            ->where('type', 'product')
            ->whereIn('id', $ids)
            ->with('user')
            ->get()
            ->keyBy('id');

        $items = [];
        $total = 0;
        $clean = [];

        foreach ($cart as $id => $qty) {
            if (!$ads->has($id)) continue;

            $ad = $ads[$id];
            if ($ad->user_id === auth()->id()) continue;

            $quantity = max(1, min(99, (int) $qty));
            $subtotal = (float) $ad->price * $quantity;

            $items[] = ['ad' => $ad, 'quantity' => $quantity, 'subtotal' => $subtotal];
            $total += $subtotal;
            $clean[$ad->id] = $quantity;
        }

        session(['cart' => $clean]);

        return [$items, $total];
    }
}
