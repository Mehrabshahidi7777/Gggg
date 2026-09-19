<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ad;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SalesController extends Controller
{
    public function index(Request $request)
    {
        $sellerId = $request->integer('seller_id') ?: null;

        $rows = OrderItem::query()
            ->select([
                'order_items.seller_id',
                DB::raw('YEAR(orders.paid_at) as sale_year'),
                DB::raw('MONTH(orders.paid_at) as sale_month'),
                DB::raw('SUM(order_items.subtotal) as gross_amount'),
                DB::raw('SUM(order_items.quantity) as quantity'),
                DB::raw('COUNT(DISTINCT order_items.order_id) as orders_count'),
            ])
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->whereNotNull('order_items.seller_id')
            ->whereNotNull('orders.paid_at')
            ->whereIn('order_items.status', ['paid', 'processing', 'shipped', 'completed'])
            ->when($sellerId, fn ($q) => $q->where('order_items.seller_id', $sellerId))
            ->groupBy('order_items.seller_id', DB::raw('YEAR(orders.paid_at)'), DB::raw('MONTH(orders.paid_at)'))
            ->orderByDesc('sale_year')
            ->orderByDesc('sale_month')
            ->get();

        $sellerIds = $rows->pluck('seller_id')->unique()->values();
        $sellers = User::query()
            ->whereIn('id', $sellerIds->all())
            ->orderBy('username')
            ->get(['id', 'name', 'username', 'mobile'])
            ->keyBy('id');

        $cardNumbers = Ad::query()
            ->whereIn('user_id', $sellerIds->all())
            ->where('type', 'product')
            ->whereNotNull('card_number')
            ->latest('id')
            ->get(['user_id', 'card_number'])
            ->unique('user_id')
            ->pluck('card_number', 'user_id');

        $periods = $rows
            ->groupBy(fn ($row) => $this->periodKey((int) $row->sale_year, (int) $row->sale_month))
            ->map(function ($periodRows, $key) use ($sellers, $cardNumbers) {
                [$year, $period] = array_map('intval', explode('-', $key));
                $monthStart = ($period * 2) - 1;
                $label = sprintf('%04d/%02d تا %04d/%02d', $year, $monthStart, $year, $monthStart + 1);

                $sellerRows = $periodRows
                    ->groupBy('seller_id')
                    ->map(function ($items, $sellerId) use ($sellers, $cardNumbers) {
                        return [
                            'seller' => $sellers->get((int) $sellerId),
                            'card_number' => $cardNumbers->get((int) $sellerId),
                            'orders_count' => (int) $items->sum('orders_count'),
                            'quantity' => (int) $items->sum('quantity'),
                            'gross_amount' => (float) $items->sum('gross_amount'),
                        ];
                    })
                    ->sortByDesc('gross_amount')
                    ->values();

                return [
                    'key' => $key,
                    'label' => $label,
                    'sellers' => $sellerRows,
                    'gross_amount' => (float) $sellerRows->sum('gross_amount'),
                    'quantity' => (int) $sellerRows->sum('quantity'),
                    'orders_count' => (int) $sellerRows->sum('orders_count'),
                ];
            })
            ->sortKeysDesc()
            ->values();

        $allSellers = User::query()
            ->whereHas('orderItems', fn ($q) => $q->whereHas('order', fn ($o) => $o->whereIn('status', ['paid', 'processing', 'shipped', 'completed'])->whereNotNull('paid_at')))
            ->orderBy('username')
            ->get(['id', 'name', 'username', 'mobile']);

        $currentPeriodKey = $this->periodKey((int) now()->year, (int) now()->month);
        $currentPeriod = $periods->firstWhere('key', $currentPeriodKey);

        return view('admin.sales.index', compact('periods', 'allSellers', 'sellerId', 'currentPeriod'));
    }

    private function periodKey(int $year, int $month): string
    {
        $period = (int) ceil($month / 2);
        return sprintf('%04d-%02d', $year, $period);
    }
}
