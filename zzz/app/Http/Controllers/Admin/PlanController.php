<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ServicePlan;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PlanController extends Controller
{
    public function index(Request $r)
    {
        $type = $r->input('type', 'service');

        $plans = ServicePlan::query()
            ->ofType($type)
            ->withCount('subscriptions')
            ->orderBy('sort_order')
            ->orderBy('months')
            ->get();

        return view('admin.plans.index', compact('plans', 'type'));
    }

    public function create(Request $r)
    {
        return view('admin.plans.form', [
            'plan' => new ServicePlan(['type' => $r->input('type', 'service')]),
        ]);
    }

    public function store(Request $r)
    {
        $data = $this->validated($r);

        ServicePlan::create($data);

        return redirect()
            ->route('admin.plans.index', ['type' => $data['type']])
            ->with('success', 'پلن جدید ثبت شد.');
    }

    public function edit(ServicePlan $plan)
    {
        return view('admin.plans.form', compact('plan'));
    }

    public function update(Request $r, ServicePlan $plan)
    {
        $data = $this->validated($r, $plan);

        $plan->update($data);

        return redirect()
            ->route('admin.plans.index', ['type' => $plan->type])
            ->with('success', 'پلن به‌روزرسانی شد.');
    }

    public function destroy(ServicePlan $plan)
    {
        /*
        | یک پلن که قبلاً کسی خریده را حذف نمی‌کنیم - چون اشتراک‌های
        | قبلی به همین ردیف وصل‌اند (service_plan_id) و حذفش یعنی
        | تاریخچه‌ی خرید آن کاربران یتیم می‌شود. به‌جایش فقط غیرفعالش
        | می‌کنیم تا دیگر قابل خرید نباشد ولی سابقه دست‌نخورده بماند.
        */
        if ($plan->subscriptions()->exists()) {
            $plan->update(['is_active' => false]);

            return back()->with(
                'success',
                'این پلن قبلاً خریداری شده، پس به‌جای حذف فقط غیرفعال شد.'
            );
        }

        $plan->delete();

        return redirect()
            ->route('admin.plans.index', ['type' => $plan->type])
            ->with('success', 'پلن حذف شد.');
    }

    private function validated(Request $r, ?ServicePlan $plan = null): array
    {
        $data = $r->validate([
            'type' => ['required', Rule::in(['service', 'product'])],
            'title' => ['required', 'string', 'max:255'],

            'months' => [
                'required',
                'integer',
                'min:1',
                'max:60',
                Rule::unique('service_plans', 'months')
                    ->where(fn ($q) => $q->where('type', $r->input('type')))
                    ->ignore($plan?->id),
            ],

            'price' => ['required', 'numeric', 'min:0'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ], [
            'months.unique' => 'برای این نوع، پلنی با همین تعداد ماه از قبل وجود دارد.',
        ]);

        $data['is_active'] = $r->boolean('is_active');

        return $data;
    }
}
