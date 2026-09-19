<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Ad;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function index()
    {
        $cart = session('cart', []);

        $ids = array_keys($cart);

        $ads = Ad::approved()
            ->where('type', 'product')
            ->whereIn('id', $ids)
            ->with('primaryImage')
            ->get()
            ->keyBy('id');

        $items = [];
        $total = 0;

        foreach ($cart as $id => $qty) {

            if (!$ads->has($id)) {
                unset($cart[$id]);
                continue;
            }

            $qty = max(1, min(99, (int) $qty));

            $ad = $ads[$id];

            $subtotal = (float) $ad->price * $qty;

            $total += $subtotal;

            $items[] = [
                'ad' => $ad,
                'quantity' => $qty,
                'subtotal' => $subtotal,
            ];

            $cart[$id] = $qty;
        }

        session(['cart' => $cart]);

        return view('front.cart.index', compact('items', 'total'));
    }

    public function add(Request $request, Ad $ad)
    {
        abort_unless(
            $ad->type === 'product'
            && $ad->status === 'approved'
            && (!$ad->expires_at || $ad->expires_at->isFuture()),
            404
        );

        if ($ad->user_id === auth()->id()) {

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'نمی‌توانید محصول خودتان را خریداری کنید.',
                ], 403);
            }

            return back()->with(
                'error',
                'نمی‌توانید محصول خودتان را خریداری کنید.'
            );
        }

        $qty = max(
            1,
            min(99, $request->integer('quantity', 1))
        );

        $cart = session('cart', []);

        $cart[$ad->id] = min(
            99,
            ($cart[$ad->id] ?? 0) + $qty
        );

        session(['cart' => $cart]);

        /*
        |--------------------------------------------------------------------------
        | AJAX / JSON Response
        |--------------------------------------------------------------------------
        */

        if ($request->expectsJson()) {

            $cartCount = array_sum($cart);

            return response()->json([
                'success' => true,
                'message' => 'محصول به سبد خرید اضافه شد.',
                'cart_count' => $cartCount,
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Normal Request
        |--------------------------------------------------------------------------
        */

        return back()->with(
            'success',
            'محصول به سبد خرید اضافه شد.'
        );
    }

    public function update(Request $request, Ad $ad)
    {
        $qty = $request->integer('quantity');

        $cart = session('cart', []);

        if ($qty < 1) {
            unset($cart[$ad->id]);
        } else {
            $cart[$ad->id] = min(99, $qty);
        }

        session(['cart' => $cart]);

        return back()->with(
            'success',
            'سبد خرید به‌روزرسانی شد.'
        );
    }

    public function remove(Ad $ad)
    {
        $cart = session('cart', []);

        unset($cart[$ad->id]);

        session(['cart' => $cart]);

        return back()->with(
            'success',
            'محصول از سبد خرید حذف شد.'
        );
    }
}