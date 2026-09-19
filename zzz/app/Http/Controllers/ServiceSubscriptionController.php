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

class ServiceSubscriptionController extends Controller
{
    public function plans(Request $request)
    {
        $plans = ServicePlan::query()
            // این where قبلاً نبود. تا قبل از این فیچر مشکلی هم نداشت،
            // چون کل جدول فقط پلن خدمات داشت. حالا که همین جدول پلن
            // محصول هم نگه می‌داره، بدون این فیلتر پلن‌های محصول هم
            // اشتباهی توی همین صفحه‌ی «پلن‌های خدمات» نمایش داده می‌شدند.
            ->where('type', 'service')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        return view('front.service-plans', compact('plans'));
    }

    public function pay(Request $request, ServicePlan $plan)
    {
        // همین‌طور اینجا: قبلاً فقط is_active چک می‌شد. بدون چک type،
        // یک نفر می‌توانست با ساختن دستیِ آدرس /service-plans/{شناسه‌ی
        // یک پلن محصول}/pay، قیمت آن پلن محصول را پرداخت کند اما چون
        // این متد صراحتاً 'type' را روی 'service' ست نمی‌کرد، به‌جای آن
        // یک اشتراک «خدمت» برایش ساخته می‌شد - یعنی جنس چیزی که خریده
        // با جنس چیزی که فعال می‌شد یکی نبود.
        abort_unless($plan->type === 'service' && $plan->is_active, 404);

        $mobile = auth()->user()->mobile;

        /*
        |--------------------------------------------------------------------------
        | Create pending subscription
        |--------------------------------------------------------------------------
        */

        $subscription = ServiceSubscription::create([
            'type' => 'service',
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
            ->detail('description', 'خرید اشتراک خدمات سازمت')
            ->detail('service_plan', $plan->title)
            ->detail('subscription_id', (string) $subscription->id);

        if ($mobile) {
            $invoice->detail('mobile', (string) $mobile);
        }

        try {
            return Payment::via('sep')
                ->callbackUrl(route('service.payment.callback', [
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

            Log::error('SEP service payment purchase failed', [
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
            ->where('type', 'service')
            ->with(['plan', 'user'])
            ->find($subscriptionId);

        if (!$subscription) {
            return redirect()
                ->route('home')
                ->with('error', 'اشتراک مربوط به این پرداخت پیدا نشد.');
        }

        /*
        |--------------------------------------------------------------------------
        | تأیید توکن، قبل از هر کار دیگری
        |--------------------------------------------------------------------------
        |
        | این آدرس (callback) بدون نیاز به ورود قبلی در دسترسه — چون ممکنه
        | نشست مرورگر کاربر بعد از برگشت از درگاه از بین رفته باشه، پایین‌تر
        | خودمون دوباره کاربر رو وارد می‌کنیم. اما دقیقاً به همین دلیل،
        | «شناسه‌ی اشتراک» به‌تنهایی هرگز نباید کافی باشه برای ورود خودکار،
        | چون یک عدد صحیح ساده و قابل‌حدسه (۱و۲و۳...). تنها مدرکی که ثابت
        | می‌کنه این درخواست واقعاً از درگاه پرداخت برای همین تراکنش خاص
        | برگشته، مطابقت «Token» با transaction_id ذخیره‌شده‌ست. تا این
        | تطابق تأیید نشه، مطلقاً به هیچ مسیری (نه موفق نه ناموفق) اجازه‌ی
        | Auth::login نمی‌دیم؛ وگرنه هرکسی با حدس‌زدن شناسه می‌تونه بدون
        | رمز عبور به‌جای صاحب اون اشتراک وارد بشه.
        */

        $token = trim((string) $request->input('Token'));
        $transactionId = trim((string) $subscription->transaction_id);

        if ($transactionId === '' || $token === '' || $transactionId !== $token) {

            Log::error('SEP service callback token mismatch', [
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
        |
        | از این‌جا به بعد توکن تأیید شده، پس ورود خودکار کاربر امن است.
        */

        if ($subscription->status === 'active') {

            Auth::login($subscription->user);
            $request->session()->regenerate();

            return redirect()
                ->route('service.panel')
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
                ->route('service.plans')
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

            Log::error('SEP service payment verification failed', [
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
                ->route('service.plans')
                ->with(
                    'error',
                    'پرداخت تأیید نشد: ' . $e->getMessage()
                );

        } catch (\Throwable $e) {

            Log::error('SEP service verification unexpected error', [
                'subscription_id' => $subscription->id,
                'transaction_id' => $transactionId,
                'exception' => $e->getMessage(),
                'exception_class' => get_class($e),
            ]);

            Auth::login($subscription->user);
            $request->session()->regenerate();

            return redirect()
                ->route('service.panel')
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

                /*
                | If the user already has an active subscription,
                | continue it from its current end date.
                |
                | Otherwise the new subscription starts now.
                */

                $current = $subscription->user
                    ->serviceSubscriptions()
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

                /*
                | The previous active subscription's remaining time is now
                | fully absorbed into this new one's end date above, so it
                | must be closed out here — otherwise the scheduler would
                | still see it as "active" and wrongly suspend the user's
                | services once that OLD end date passes, even though the
                | new subscription should keep them active well beyond it.
                */

                if ($current) {
                    $current->update(['status' => 'expired']);
                }

                $subscription->update([
                    'status' => 'active',
                    'paid_at' => now(),
                    'starts_at' => $start,
                    'ends_at' => $end,

                    /*
                    | Six months after subscription expiry,
                    | suspended services will be permanently deleted.
                    */
                    'grace_until' => $end->copy()->addMonths(6),

                    'reference_id' => (string) $receipt->getReferenceId(),
                ]);

                /*
                |--------------------------------------------------------------------------
                | IMPORTANT:
                | Reactivate suspended services after successful payment.
                |--------------------------------------------------------------------------
                */

                $reactivatedCount = Ad::query()
                    ->where('user_id', $subscription->user_id)
                    ->where('type', 'service')
                    ->where('is_suspended', true)
                    ->update([
                        'is_suspended' => false,
                        'suspended_at' => null,
                    ]);
            });

        } catch (\Throwable $e) {

            Log::critical(
                'SEP verified payment could not activate subscription',
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
                ->route('service.panel')
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
            ->route('service.panel')
            ->with(
                'success',
                'پرداخت با موفقیت تأیید شد.'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Service Panel
    |--------------------------------------------------------------------------
    */

    public function panel()
    {
        $user = auth()->user();

        /*
        |--------------------------------------------------------------------------
        | Safety sync
        |--------------------------------------------------------------------------
        |
        | Scheduler نیز این کار را انجام می‌دهد.
        | این بخش فقط یک لایه اطمینان اضافه است.
        |--------------------------------------------------------------------------
        */

        $subscription = $user
            ->serviceSubscriptions()
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
                ->where('type', 'service')
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
        |
        | اگر یک اشتراک واقعاً «در حال حاضر» معتبر باشد (status=active و
        | starts_at <= الان <= ends_at)، همان را نشان بده؛ حتی اگر کاربر
        | بعداً یک اشتراک جدید و پرداخت‌نشده (pending) هم ساخته باشد.
        | در غیر این صورت، آخرین رکورد را برای نمایش وضعیت (منقضی/در
        | انتظار پرداخت/...) نشان بده.
        |--------------------------------------------------------------------------
        */

        $subscription = $user
            ->serviceSubscriptions()
            ->with('plan')
            ->current()
            ->latest('ends_at')
            ->first()
            ?? $user
                ->serviceSubscriptions()
                ->with('plan')
                ->latest('id')
                ->first();

        /*
        | برای نمایش، "فعال بودن" را از روی همین قانون current() تعیین
        | می‌کنیم، نه صرفاً از روی فیلد status. چون وقتی کاربر زودتر از
        | پایان تمدید کرده، ممکن است رکوردی که همین الان را پوشش می‌دهد
        | در دیتابیس status=expired داشته باشد (چون جایش را به تمدید
        | بعدی داده)، ولی از نظر کاربر و واقعیت، او همین الان مشترک است.
        */
        $isCurrentlyActive = $subscription
            && $subscription->ends_at
            && $subscription->ends_at->isFuture()
            && (!$subscription->starts_at || $subscription->starts_at->isPast())
            && in_array($subscription->status, ['active', 'expired']);

        /*
        |--------------------------------------------------------------------------
        | بازه‌ی واقعیِ اشتراکِ پیوسته (زنجیره‌ی تمدیدها)
        |--------------------------------------------------------------------------
        |
        | وقتی کاربر چند بار زودتر از پایان تمدید می‌کند، هر تمدید یک
        | ردیف جدید در دیتابیس می‌سازد که دقیقاً از جایی که قبلی تمام
        | می‌شود شروع می‌شود (بدون فاصله). $subscription که بالاتر پیدا
        | شد فقط همان یک تکه‌ای است که «همین الان» را پوشش می‌دهد —
        | مثلاً اولین ماه از سه ماهی که خریده. برای اینکه «شروع اشتراک»
        | و «پایان اشتراک» درست نشان داده شوند (شروع = اولین خریدِ همین
        | زنجیره‌ی بی‌وقفه، پایان = آخرین ماهی که تا الان برایش پول داده)
        | باید این زنجیره را از دو طرف دنبال کنیم. به محض اینکه یک جای
        | زنجیره واقعاً پاره شود (یعنی یک وقفه‌ی واقعی/تعلیق پیش بیاید و
        | بعد دوباره از نو خریداری شود)، این زنجیره‌یابی خودش متوقف
        | می‌شود و شروعِ نمایش‌داده‌شده هم خودش را با همان خرید تازه
        | هماهنگ می‌کند.
        |--------------------------------------------------------------------------
        */

        $chainStart = $subscription?->starts_at;
        $chainEnd = $subscription?->ends_at;

        if ($subscription && $subscription->starts_at && $subscription->ends_at) {

            $earliest = $subscription;

            while (
                $prev = $user->serviceSubscriptions()
                    ->whereIn('status', ['active', 'expired'])
                    ->where('ends_at', $earliest->starts_at)
                    ->first()
            ) {
                $earliest = $prev;
            }

            $latest = $subscription;

            while (
                $next = $user->serviceSubscriptions()
                    ->whereIn('status', ['active', 'expired'])
                    ->where('starts_at', $latest->ends_at)
                    ->first()
            ) {
                $latest = $next;
            }

            $chainStart = $earliest->starts_at;
            $chainEnd = $latest->ends_at;
        }

        $services = $user
            ->ads()
            ->where('type', 'service')
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
            ])
            ->latest()
            ->get();

        $serviceCount = $services->count();

        $leadsThisMonth = $services->sum('leads_this_month');
        $leadsTotal = $services->sum('leads_total');

        return view(
            'front.service-panel',
            compact(
                'subscription',
                'services',
                'serviceCount',
                'leadsThisMonth',
                'leadsTotal',
                'isCurrentlyActive',
                'chainStart',
                'chainEnd'
            )
        );
    }
}