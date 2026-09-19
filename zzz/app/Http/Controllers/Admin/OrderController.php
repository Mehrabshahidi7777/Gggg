<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
class OrderController extends Controller {
    public function index(Request $request){$q=Order::with(['buyer','items.seller'])->withCount('items')->latest();if($request->filled('status'))$q->where('status',$request->status);if($request->filled('search'))$q->where(fn($x)=>$x->where('order_number','like','%'.$request->search.'%')->orWhere('phone','like','%'.$request->search.'%'));return view('admin.orders.index',['orders'=>$q->paginate(25)->withQueryString()]);}
    public function show(Order $order){$order->load(['buyer','items.seller','items.ad']);return view('admin.orders.show',compact('order'));}
    public function update(Request $request,Order $order){$data=$request->validate(['status'=>['required','in:pending,paid,processing,shipped,completed,cancelled']]);$order->update(['status'=>$data['status']]);
        $order->items()->update(['status'=>$data['status']]);
        return back()->with('success','وضعیت سفارش و اقلام آن تغییر کرد.');}
}
