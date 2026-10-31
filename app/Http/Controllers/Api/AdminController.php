<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;

class AdminController extends Controller
{
    public function overview()
    {
        return response()->json([
            'users' => User::count(),
            'products' => Product::count(),
            'campaigns' => Campaign::count(),
            'orders' => Order::count(),
            'revenue' => Order::sum('total'),
        ]);
    }

    public function orders()
    {
        return response()->json(Order::with('items.product')->latest()->take(20)->get());
    }

    public function products()
    {
        return response()->json(Product::latest()->take(20)->get());
    }

    public function campaigns()
    {
        return response()->json(Campaign::latest()->take(20)->get());
    }
}
