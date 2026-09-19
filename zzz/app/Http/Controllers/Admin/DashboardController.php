<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller; use App\Models\Ad; use App\Models\Category; use App\Models\User; use App\Models\ContactMessage; use App\Models\Order;
class DashboardController extends Controller { public function index(){return view('admin.dashboard.index',['stats'=>['ads'=>Ad::count(),'pending'=>Ad::where('status','pending')->count(),'users'=>User::count(),'categories'=>Category::count(),'messages'=>ContactMessage::where('is_read',false)->count(),'sales'=>Order::whereIn('status',['paid','processing','shipped','completed'])->count()],'latestAds'=>Ad::with(['user','category'])->latest()->limit(10)->get()]);} }
