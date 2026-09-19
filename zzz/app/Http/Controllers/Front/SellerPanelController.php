<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\OrderItem;
use Illuminate\Http\Request;

class SellerPanelController extends Controller
{
    public function index()
    {
        $user=auth()->user();
        $productAds=$user->ads()->where('type','product')->latest()->get();
        $serviceAds=$user->ads()->where('type','service')->latest()->get();
        $orderItems=$user->orderItems()->with(['order.buyer','ad'])->whereIn('status',['paid','processing','shipped','completed'])->latest()->paginate(15);
        $subscription=$user->serviceSubscriptions()->with('plan')->latest()->first();
        return view('front.seller.index',compact('productAds','serviceAds','orderItems','subscription'));
    }

    public function updateOrderItem(Request $request, OrderItem $item)
    {
        abort_unless($item->seller_id === auth()->id(),403);
        $data=$request->validate(['status'=>['required','in:processing,shipped,completed']]);
        $item->load('order');

        if (in_array($item->order->status,['pending','cancelled'],true) || $item->status === 'cancelled') {
            return back()->with('error','این سفارش در وضعیت قابل تغییر نیست.');
        }

        $allowed = match ($item->status) {
            'paid' => ['processing'],
            'processing' => ['shipped'],
            'shipped' => ['completed'],
            'completed' => [],
            default => [],
        };

        if (!in_array($data['status'],$allowed,true)) {
            return back()->with('error','ترتیب وضعیت سفارش قابل برگشت یا پرش نیست.');
        }

        $item->update(['status'=>$data['status']]);
        $item->order->syncStatusFromItems();
        return back()->with('success','وضعیت همین محصول در سفارش به‌روزرسانی شد.');
    }
}
