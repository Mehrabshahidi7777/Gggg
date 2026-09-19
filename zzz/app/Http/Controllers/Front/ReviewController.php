<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Review;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function store(Request $request, Order $order, OrderItem $item)
    {
        abort_unless($order->buyer_id === auth()->id() && $item->order_id === $order->id && $item->seller_id, 404);

        if ($item->status !== 'completed') {
            return back()->with('error','ثبت نظر فقط بعد از تکمیل سفارش امکان‌پذیر است.');
        }

        if (Review::where('order_item_id',$item->id)->exists()) {
            return back()->with('error','برای این محصول قبلاً نظر ثبت کرده‌اید.');
        }

        $data=$request->validate([
            'rating'=>['required','integer','between:1,5'],
            'comment'=>['nullable','string','max:1000'],
        ]);

        Review::create([
            'order_item_id'=>$item->id,
            'buyer_id'=>auth()->id(),
            'seller_id'=>$item->seller_id,
            'ad_id'=>$item->ad_id,
            'rating'=>$data['rating'],
            'comment'=>$data['comment'] ?? null,
        ]);

        return back()->with('success','نظر شما با موفقیت ثبت شد.');
    }
}
