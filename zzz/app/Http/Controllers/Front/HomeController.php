<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Ad;
use App\Models\Category;

class HomeController extends Controller
{
    public function index()
    {
        $featuredProducts = Ad::approved()
            ->byType('product')
            ->featured()
            ->with([
                'category',
                'province',
                'primaryImage',
            ])
            ->latest()
            ->limit(8)
            ->get();

        $featuredServices = Ad::approved()
            ->byType('service')
            ->featured()
            ->with([
                'category',
                'province',
                'primaryImage',
            ])
            ->latest()
            ->limit(8)
            ->get();

        $categories = Category::where('is_active', true)
            ->withCount([
                'ads' => function ($query) {
                    $query->approved();
                },
            ])
            ->orderByDesc('ads_count')
            ->limit(8)
            ->get();

        return view(
            'front.home',
            compact(
                'featuredProducts',
                'featuredServices',
                'categories'
            )
        );
    }
}