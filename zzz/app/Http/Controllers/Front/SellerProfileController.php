<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\User;

class SellerProfileController extends Controller
{
    public function show(User $user)
    {
        $products = $user->ads()->approved()->where('type','product')->with(['category','province','city','primaryImage'])->latest()->paginate(12, ['*'], 'products_page');
        $services = $user->ads()->approved()->where('type','service')->with(['category','province','city','primaryImage'])->latest()->limit(8)->get();
        $reviewQuery = $user->receivedReviews()->with('buyer')->latest();
        $reviews = $reviewQuery->paginate(8, ['*'], 'reviews_page');
        $averageRating = round((float) ($user->receivedReviews()->avg('rating') ?? 0), 1);
        $reviewCount = $user->receivedReviews()->count();
        $salesCount = $user->orderItems()->where('status','completed')->sum('quantity');

        return view('front.seller-profile', compact('user','products','services','reviews','averageRating','reviewCount','salesCount'));
    }
}
