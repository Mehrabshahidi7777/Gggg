<?php

namespace App\Http\Controllers;

use App\Models\Ad;
use App\Models\ServicePlan;
use App\Models\ServiceSubscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Shetabit\Multipay\Exceptions\InvalidPaymentException;
use Shetabit\Multipay\Invoice;
use Shetabit\Payment\Facade\Payment;

/*
|--------------------------------------------------------------------------
| این کنترلر عیناً همون منطق ServiceSubscriptionController رو برای نوع
| «محصول» تکرار می‌کنه (پلن، پرداخت، callback، پنل). عمداً یک کنترلر
| مشترک پارامتری نشد و به‌جاش کپی شد، تا فلوی پرداخت خدماتِ درحال‌کارِ
| فعلی (که مشتری واقعی داره ازش استفاده می‌کنه) کوچیک‌ترین تغییری نکنه
| و ریسک این فیچر جدید بهش سرایت نکنه.
|--------------------------------------------------------------------------
*/

class ProductSubscriptionController extends Controller
{
    public function plans(Request $request)
    {
        $plans = ServicePlan::query()
            ->where('type', 'product')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        return view('front.product-plans', compact('plans'));
    }

    public function pay(Request $request, ServicePlan $plan)
    {
        abort_unless($plan->type === 'product' && $plan->is_active, 404);

        $mobile = auth()->user()->mobile;

        /*
        |--------------------------------------------------------------------------
        | Create pending subscription
        |--------------------------------------------------------------------------
        */

        $subscription = ServiceSubscription::create([
            'type' => 'product',
            'user_id' => auth()->id(),
            'service_plan_id' => $plan->id,
            'amount' => $plan->price,
            'starts_at' => null,
            'ends_at' => null,
            'grace_until' => null,
            'paid_at' => null,
            'transaction_id' => null,
            'reference_id' => null,
            'status' => 'pending',
        ]);

        /*
        |--------------------------------------------------------------------------
        | SEP Invoice
        |--------------------------------------------------------------------------
        */

        $invoice = (new Invoice)
            ->amount((int) $plan->price)
            ->detail('description', 'خرید اشتراک محصولات سازمت')
            ->detail('product_plan', $plan->title)
            ->detail('subscription_id', (string) $subscription->id);

        if ($mobile) {
            $invoice->detail('mobile', (string) $mobile);
        }

        try {
            return Payment::via('sep')
                ->callbackUrl(route('product.payment.callback', [
                    'subscription' => $subscription->id,
                ]))
                ->purchase(
                    $invoice,
                    function ($driver, $transactionId) use ($subscription) {
                        $subscription->update([
                            'transaction_id' => (string) $transactionId,
                        ]);
                    }
                )
                ->pay()
                ->render();

        } catch (\Throwable $e) {

            Log::error('SEP product payment purchase failed', [
                'subscription_id' => $subscription->id,
                'plan_id' => $plan->id,
                'amount_toman' => (int) $plan->price,
                'exception' => $e->getMessage(),
                'exception_class' => get_class($e),
            ]);

            $subscription->update([
                'status' => 'cancelled',
            ]);

            return back()->with(
                'error',
                'اتصال به درگاه پرداخت انجام نشد: ' . $e->getMessage()
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | SEP Callback
    |--------------------------------------------------------------------------
    */

    public function callback(Request $request)
    {
        $subscriptionId = $request->integer('subscription');

        if (!$subscriptionId) {
            return redirect()
                ->route('home')
                ->with('error', 'شناسه اشتراک پرداخت پیدا نشد.');
        }

        $subscription = ServiceSubscription::query()
            ->where('type', 'product')
            ->with(['plan', 'user'])
            ->find($subscriptionId);

        if (!$subscription) {
            return redirect()
                ->route('home')
                ->with('error', 'اشتراک مربوط به این پرداخت پیدا نشد.');
        }

        /*
        |--------------------------------------------------------------------------
        | تأیید توکن، قبل از هر کار دیگری (دقیقاً همون منطق سرویس)
        |--------------------------------------------------------------------------
        */

        $token = trim((string) $request->input('Token'));
        $transactionId = trim((string) $subscription->transaction_id);

        if ($transactionId === '' || $token === '' || $transactionId !== $token) {

            Log::error('SEP product callback token mismatch', [
                'subscription_id' => $subscription->id,
                'stored_transaction_id' => $subscription->transaction_id,
                'callback_token' => $token,
                'ip' => $request->ip(),
            ]);

            return redirect()
                ->route('home')
                ->with('error', 'اطلاعات تراکنش نامعتبر است.');
        }

        /*
        |--------------------------------------------------------------------------
        | Prevent duplicate callback
        |--------------------------------------------------------------------------
        */

        if ($subscription->status === 'active') {

            Auth::login($subscription->user);
            $request->session()->regenerate();

            return redirect()
                ->route('product.panel')
                ->with(
                    'success',
                    'این پرداخت قبلاً ثبت شده و اشتراک شما فعال است.'
                );
        }

        /*
        |--------------------------------------------------------------------------
        | SEP status
        |--------------------------------------------------------------------------
        */

        if ((int) $request->input('Status') !== 2) {

            $subscription->update([
                'status' => 'cancelled',
            ]);

            Auth::login($subscription->user);
            $request->session()->regenerate();

            return redirect()
                ->route('product.plans')
                ->with(
                    'error',
                    'پرداخت اشتراک لغو شد یا با موفقیت انجام نشد.'
                );
        }

        /*
        |--------------------------------------------------------------------------
        | Verify payment with SEP
        |--------------------------------------------------------------------------
        */

        try {

            $receipt = Payment::via('sep')
                ->amount((int) $subscription->amount)
                ->transactionId($transactionId)
                ->verify();

        } catch (InvalidPaymentException $e) {

            Log::error('SEP product payment verification failed', [
                'subscription_id' => $subscription->id,
                'transaction_id' => $transactionId,
                'exception' => $e->getMessage(),
                'exception_class' => get_class($e),
            ]);

            $subscription->update([
                'status' => 'cancelled',
            ]);

            Auth::login($subscription->user);
            $request->session()->regenerate();

            return redirect()
                ->route('product.plans')
                ->with(
                    'error',
                    'پرداخت تأیید نشد: ' . $e->getMessage()
                );

        } catch (\Throwable $e) {

            Log::error('SEP product verification unexpected error', [
                'subscription_id' => $subscription->id,
                'transaction_id' => $transactionId,
                'exception' => $e->getMessage(),
                'exception_class' => get_class($e),
            ]);

            Auth::login($subscription->user);
            $request->session()->regenerate();

            return redirect()
                ->route('product.panel')
                ->with(
                    'error',
                    'خطا در تأیید پرداخت: ' . $e->getMessage()
                );
        }

        /*
        |--------------------------------------------------------------------------
        | Payment verified successfully
        |--------------------------------------------------------------------------
        */

        $reactivatedCount = 0;

        try {

            DB::transaction(function () use ($subscription, $receipt, &$reactivatedCount) {

                $current = $subscription->user
                    ->productSubscriptions()
                    ->where('status', 'active')
                    ->where('ends_at', '>', now())
                    ->latest('ends_at')
                    ->first();

                $start = $current
                    ? $current->ends_at->copy()
                    : now();

                $end = $start
                    ->copy()
                    ->addMonths($subscription->plan->months);

                if ($current) {
                    $current->update(['status' => 'expired']);
                }

                $subscription->update([
                    'status' => 'active',
                    'paid_at' => now(),
                    'starts_at' => $start,
                    'ends_at' => $end,

                    /*
                    | شش ماه پس از پایان اشتراک، محصولاتِ تعلیق‌شده
                    | برای همیشه حذف می‌شوند.
                    */
                    'grace_until' => $end->copy()->addMonths(6),

                    'reference_id' => (string) $receipt->getReferenceId(),
                ]);

                /*
                |--------------------------------------------------------------------------
                | IMPORTANT:
                | Reactivate suspended products after successful payment.
                |--------------------------------------------------------------------------
                */

                $reactivatedCount = Ad::query()
                    ->where('user_id', $subscription->user_id)
                    ->where('type', 'product')
                    ->where('is_suspended', true)
                    ->update([
                        'is_suspended' => false,
                        'suspended_at' => null,
                    ]);
            });

        } catch (\Throwable $e) {

            Log::critical(
                'SEP verified payment could not activate product subscription',
                [
                    'subscription_id' => $subscription->id,
                    'transaction_id' => $transactionId,
                    'exception' => $e->getMessage(),
                    'exception_class' => get_class($e),
                ]
            );

            Auth::login($subscription->user);
            $request->session()->regenerate();

            return redirect()
                ->route('product.panel')
                ->with(
                    'error',
                    'پرداخت تأیید شد، اما فعال‌سازی اشتراک در پایگاه داده با خطا مواجه شد.'
                );
        }

        /*
        |--------------------------------------------------------------------------
        | Login user
        |--------------------------------------------------------------------------
        */

        Auth::login($subscription->user);
        $request->session()->regenerate();

        return redirect()
            ->route('product.panel')
            ->with(
                'success',
                'پرداخت با موفقیت تأیید شد.'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Product Panel
    |--------------------------------------------------------------------------
    */

    public function panel()
    {
        $user = auth()->user();

        /*
        |--------------------------------------------------------------------------
        | Safety sync (همون کاری که scheduler هم انجام می‌ده)
        |--------------------------------------------------------------------------
        */

        $subscription = $user
            ->productSubscriptions()
            ->with('plan')
            ->latest('id')
            ->first();

        if (
            $subscription &&
            $subscription->status === 'active' &&
            $subscription->ends_at &&
            $subscription->ends_at->isPast()
        ) {

            $subscription->update([
                'status' => 'expired',
                'grace_until' => $subscription->ends_at
                    ->copy()
                    ->addMonths(6),
            ]);

            $user->ads()
                ->where('type', 'product')
                ->where('is_suspended', false)
                ->update([
                    'is_suspended' => true,
                    'suspended_at' => $subscription->ends_at,
                ]);
        }

        if (
            $subscription &&
            $subscription->status === 'expired' &&
            $subscription->grace_until &&
            $subscription->grace_until->isPast()
        ) {

            $subscription->update([
                'status' => 'suspended',
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | نمایش اشتراکی که واقعاً الان اعتبار دارد
        |--------------------------------------------------------------------------
        */

        $subscription = $user
            ->productSubscriptions()
            ->with('plan')
            ->current()
            ->latest('ends_at')
            ->first()
            ?? $user
                ->productSubscriptions()
                ->with('plan')
                ->latest('id')
                ->first();

        $isCurrentlyActive = $subscription
            && $subscription->ends_at
            && $subscription->ends_at->isFuture()
            && (!$subscription->starts_at || $subscription->starts_at->isPast())
            && in_array($subscription->status, ['active', 'expired']);

        /*
        |--------------------------------------------------------------------------
        | بازه‌ی واقعیِ اشتراکِ پیوسته (زنجیره‌ی تمدیدها)
        |--------------------------------------------------------------------------
        */

        $chainStart = $subscription?->starts_at;
        $chainEnd = $subscription?->ends_at;

        if ($subscription && $subscription->starts_at && $subscription->ends_at) {

            $earliest = $subscription;

            while (
                $prev = $user->productSubscriptions()
                    ->whereIn('status', ['active', 'expired'])
                    ->where('ends_at', $earliest->starts_at)
                    ->first()
            ) {
                $earliest = $prev;
            }

            $latest = $subscription;

            while (
                $next = $user->productSubscriptions()
                    ->whereIn('status', ['active', 'expired'])
                    ->where('starts_at', $latest->ends_at)
                    ->first()
            ) {
                $latest = $next;
            }

            $chainStart = $earliest->starts_at;
            $chainEnd = $latest->ends_at;
        }

        $products = $user
            ->ads()
            ->where('type', 'product')
            ->withCount([
                /*
                | تعداد دفعاتی که بازدیدکننده‌ها شماره‌ی این آگهی را
                | دیده‌اند - یعنی سرنخ واقعی، نه صرفاً بازدید صفحه.
                | همین عدد است که به ارائه‌دهنده نشان می‌دهد اشتراکش
                | ارزش داشته یا نه.
                */
                'contactReveals as leads_total',
                'contactReveals as leads_this_month' => fn ($q) => $q->where(
                    'created_at', '>=', now()->startOfMonth()
                ),

                /*
                | ماه گذشته، برای مقایسه.
                |
                | عددِ تنها به ارائه‌دهنده نمی‌گوید اوضاع بهتر شده یا
                | بدتر - و همین سؤال است که تصمیم تمدید را می‌سازد.
                | پس بازه‌ی *کاملِ* ماه قبل شمرده می‌شود، نه «۳۰ روز
                | گذشته»، تا با «این ماه» هم‌جنس باشد.
                */
                'contactReveals as leads_last_month' => fn ($q) => $q
                    ->where('created_at', '>=', now()->subMonthNoOverflow()->startOfMonth())
                    ->where('created_at', '<', now()->startOfMonth()),
            ])
            ->latest()
            ->get();

        $productCount = $products->count();

        $leadsThisMonth = $products->sum('leads_this_month');
        $leadsLastMonth = $products->sum('leads_last_month');
        $leadsTotal = $products->sum('leads_total');

        /*
        | بازدید و نرخ تبدیل.
        |
        | «بازدید» یعنی کسی صفحه‌ی آگهی را باز کرده؛ «تماس» یعنی روی
        | نمایش شماره زده. نسبتشان می‌گوید آگهی چقدر خوب نوشته شده:
        | بازدید زیاد با تماس کم یعنی عنوان و عکس جذب می‌کند ولی
        | محتوا قانع نمی‌کند.
        */
        $viewsTotal = $products->sum('views_count');

        /*
        | پرتماس‌ترین آگهیِ این ماه، تا ارائه‌دهنده بداند کدام یکی
        | واقعاً کار می‌کند. مساوی‌ها اهمیتی ندارند؛ یکی کافی است.
        | وقتی هیچ تماسی نبوده، هیچ‌کدام «بهترین» نیست.
        */
        $bestPerformerId = $products->where('leads_this_month', '>', 0)
            ->sortByDesc('leads_this_month')
            ->first()?->id;

        return view(
            'front.product-panel',
            compact(
                'subscription',
                'products',
                'productCount',
                'leadsThisMonth',
                'leadsLastMonth',
                'leadsTotal',
                'viewsTotal',
                'bestPerformerId',
                'isCurrentlyActive',
                'chainStart',
                'chainEnd'
            )
        );
    }
}
