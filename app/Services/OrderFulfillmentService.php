<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class OrderFulfillmentService
{
    public function transition(int $orderId, string $action, ?string $deliveryReference = null, ?string $reason = null, ?int $actorId = null): array
    {
        return DB::transaction(function () use ($orderId, $action, $deliveryReference, $reason, $actorId) {
            // Lock the product before the order to follow checkout lock ordering.
            $productId = DB::table('order_items')->where('order_id', $orderId)->value('product_id');
            if ($productId === null) abort(404);
            $product = Product::whereKey($productId)->lockForUpdate()->firstOrFail();
            $order = Order::whereKey($orderId)->lockForUpdate()->firstOrFail();

            $next = match ($action) {
                'process' => $order->status === 'pending' ? 'processing' : null,
                'ship' => $order->status === 'processing' ? 'shipped' : null,
                'deliver' => $order->status === 'shipped' ? 'delivered' : null,
                'cancel' => in_array($order->status, ['pending', 'processing'], true) ? 'cancelled' : null,
                default => null,
            };
            if ($next === null) return ['success'=>false,'message'=>'Invalid order status transition'];

            if ($action === 'ship' && !$deliveryReference) {
                return ['success'=>false,'message'=>'Delivery reference is required'];
            }

            if ($action === 'cancel') {
                // Orders already consumed stock at checkout. Return it exactly once.
                foreach ($order->items as $item) {
                    if ($item->product_id == $product->id) $product->increment('stock', $item->quantity);
                }
                $order->cancelled_at = now();
                $order->cancel_reason = $reason;
            }
            if ($action === 'ship') {
                $order->shipped_at = now();
                $order->delivery_reference = $deliveryReference;
            }
            if ($action === 'deliver') $order->delivered_at = now();

            $previousStatus = $order->status;
            $order->status = $next;
            $order->save();
            DB::table('order_events')->insert([
                'order_id'=>$order->id,'actor_id'=>$actorId,'action'=>$action,
                'from_status'=>$previousStatus,'to_status'=>$next,
                'note'=>$action === 'ship' ? $deliveryReference : ($action === 'cancel' ? $reason : null),
                'created_at'=>now(),'updated_at'=>now(),
            ]);
            return ['success'=>true,'order'=>$order->fresh()->load('items')];
        }, 3);
    }

    public function collectCod(int $orderId, ?int $actorId = null): array
    {
        return DB::transaction(function () use ($orderId, $actorId) {
            $order=Order::whereKey($orderId)->lockForUpdate()->firstOrFail();
            if ($order->status !== 'delivered' || $order->payment_method !== 'cash_on_delivery'
                || $order->cod_collected_at !== null) {
                return ['success'=>false,'message'=>'COD can only be recorded once after delivery'];
            }
            $order->cod_collected_at=now();
            $order->save();
            DB::table('order_events')->insert([
                'order_id'=>$order->id,'actor_id'=>$actorId,'action'=>'collect_cod',
                'from_status'=>$order->status,'to_status'=>$order->status,
                'note'=>'COD collection recorded', 'created_at'=>now(),'updated_at'=>now(),
            ]);
            return ['success'=>true,'order'=>$order->fresh()];
        },3);
    }
}
