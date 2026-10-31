<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'items' => 'required|array',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'payment_method' => 'required|string',
            'address_id' => 'nullable|exists:addresses,id',
        ]);

        $items = $request->items;
        $total = 0;

        foreach ($items as $item) {
            $product = Product::findOrFail($item['product_id']);
            $unitPrice = $product->sale_price ?? $product->regular_price;
            $total += $unitPrice * $item['quantity'];
        }

        $order = Order::create([
            'user_id' => Auth::id(),
            'store_id' => null,
            'campaign_id' => null,
            'total' => $total,
            'commission' => $total * 0.10,
            'payment_method' => $request->payment_method,
            'status' => 'pending',
            'address_id' => $request->address_id ?? null,
        ]);

        foreach ($items as $item) {
            $product = Product::findOrFail($item['product_id']);
            $unitPrice = $product->sale_price ?? $product->regular_price;

            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $product->id,
                'quantity' => $item['quantity'],
                'unit_price' => $unitPrice,
                'total_price' => $unitPrice * $item['quantity'],
            ]);
        }

        return response()->json([
            'message' => 'تم إنشاء الطلب بنجاح',
            'order' => $order,
        ], 201);
    }

    public function myOrders()
    {
        $orders = Order::with('items.product')->where('user_id', Auth::id())->get();

        return response()->json($orders);
    }
}
