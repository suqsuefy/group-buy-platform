<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\Order;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Support\Facades\DB;

class CatalogModerationService
{
    public function setStoreStatus(int $id, string $status, int $actorId): array
    {
        return DB::transaction(function () use ($id, $status, $actorId) {
            $store = Store::whereKey($id)->lockForUpdate()->firstOrFail();
            $from = $store->status;
            if ($from === $status) return ['success'=>false,'message'=>'Store is already in this status'];
            if (!in_array($status, ['active','blocked'], true)) return ['success'=>false,'message'=>'Invalid store status'];
            if ($status === 'blocked' && (
                Campaign::where('store_id',$id)->where('status','active')->exists()
                || Order::where('store_id',$id)->whereIn('status',['pending','processing','shipped'])->exists()
            )) return ['success'=>false,'message'=>'Resolve active campaigns and orders before blocking'];
            $store->status = $status;
            $store->save();
            // Registration creates customer accounts. Only an admin-approved
            // store promotes its owner to a vendor (never on self-submission).
            if ($status === 'active' && $store->owner?->role === 'customer') {
                $store->owner->update(['role' => 'vendor']);
            }
            $this->record('store', $id, $from, $status, $actorId);
            return ['success'=>true,'store'=>$store->fresh()];
        }, 3);
    }

    public function setProductStatus(int $id, string $status, int $actorId): array
    {
        return DB::transaction(function () use ($id, $status, $actorId) {
            $product = Product::whereKey($id)->lockForUpdate()->firstOrFail();
            $from = $product->status;
            if ($from === $status) return ['success'=>false,'message'=>'Product is already in this status'];
            if (!in_array($status, ['active','inactive'], true)) return ['success'=>false,'message'=>'Invalid product status'];
            if ($status === 'active' && $product->store->status !== 'active')
                return ['success'=>false,'message'=>'Approve the store first'];
            if ($status === 'inactive' && Campaign::where('product_id',$id)->where('status','active')->exists())
                return ['success'=>false,'message'=>'Resolve active campaigns before deactivating product'];
            $product->status = $status;
            $product->save();
            $this->record('product', $id, $from, $status, $actorId);
            return ['success'=>true,'product'=>$product->fresh()];
        }, 3);
    }

    private function record(string $type, int $id, string $from, string $to, int $actorId): void
    {
        DB::table('catalog_moderation_events')->insert([
            'actor_id'=>$actorId,'entity_type'=>$type,'entity_id'=>$id,
            'from_status'=>$from,'to_status'=>$to,'created_at'=>now(),'updated_at'=>now(),
        ]);
    }
}
