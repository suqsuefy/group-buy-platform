<?php
namespace App\Services;
use App\Models\Campaign;
use App\Models\CampaignParticipant;
use Illuminate\Support\Facades\DB;
class CampaignService {
 public function __construct(private ProductInventoryService $inventory) {}

 public function joinCampaign(int $campaignId,int $userId,int $quantity=1,?string $idempotencyKey=null):array {
  return DB::transaction(function()use($campaignId,$userId,$quantity,$idempotencyKey){
   $productId=Campaign::whereKey($campaignId)->value('product_id');
   if (!$productId) abort(404);
   $product=\App\Models\Product::whereKey($productId)->lockForUpdate()->firstOrFail();
   $c=Campaign::whereKey($campaignId)->lockForUpdate()->firstOrFail();
   if($c->status!=='active'||now()->lt($c->start_at)||now()->gte($c->end_at))
    return ['success'=>false,'message'=>'Campaign is not open'];
   if($quantity<1) return ['success'=>false,'message'=>'Invalid quantity'];
   // A retry must be checked after the product/campaign locks. Otherwise two
   // simultaneous requests with the same key may both increment participation.
   if ($idempotencyKey !== null) {
    $existing=DB::table('campaign_join_requests')->where('campaign_id',$campaignId)->where('user_id',$userId)->where('idempotency_key',$idempotencyKey)->first();
    if ($existing) {
     if ((int)$existing->quantity !== $quantity) return ['success'=>false,'message'=>'Idempotency key reused with different quantity'];
     return ['success'=>true,'message'=>'Previously joined (retry)','replayed'=>true];
    }
   }
   $p=CampaignParticipant::where('campaign_id',$campaignId)->where('user_id',$userId)->first();
   if($p && $p->status==='cancelled') $p->quantity=0;
   $count=$c->participants()->where('status','joined')->where('user_id','!=',$userId)->count();
   $totalUnits=(int)$c->participants()->where('status','joined')->where('user_id','!=',$userId)->sum('quantity')+($p?$p->quantity:0)+$quantity;
   if($count+1>$c->target_participants || $this->inventory->outstandingJoinedUnits($product->id)+$quantity>$product->stock)
    return ['success'=>false,'message'=>'Campaign capacity or stock exceeded'];
   if(!$p) $p=new CampaignParticipant(['campaign_id'=>$campaignId,'user_id'=>$userId,'quantity'=>0]);
   $p->quantity+=$quantity;$p->status='joined';$p->save();
   $c->current_participants=$c->participants()->where('status','joined')->count();$c->save();
   if ($idempotencyKey !== null) DB::table('campaign_join_requests')->insert(['campaign_id'=>$campaignId,'user_id'=>$userId,'idempotency_key'=>$idempotencyKey,'quantity'=>$quantity,'created_at'=>now(),'updated_at'=>now()]);
   return ['success'=>true,'message'=>'Joined campaign','campaign'=>$c,'participant'=>$p];
  });
 }
 public function confirmCampaign(int $campaignId):array {
  return DB::transaction(function()use($campaignId){
   $c=Campaign::whereKey($campaignId)->lockForUpdate()->firstOrFail();
   if($c->status!=='active') return ['success'=>false,'message'=>'Campaign not active'];
   if(now()->lt($c->start_at)||now()->gte($c->end_at))return ['success'=>false,'message'=>'Campaign outside its dates'];
   $n=$c->participants()->where('status','joined')->count();
   if($n<$c->min_participants)return ['success'=>false,'message'=>'Minimum not reached','current_participants'=>$n];
   $c->status='fulfilled';$c->current_participants=$n;$c->save();
   return ['success'=>true,'message'=>'Campaign confirmed','campaign'=>$c];
  });
 }
 /**
  * Cancel only campaigns with no checkout orders. Completed campaigns are not
  * reversibly cancelled here because payments/fulfilment require a separate policy.
  * The product-first lock order matches join/checkout.
  */
 public function cancelCampaign(int $campaignId): array {
  return DB::transaction(function () use ($campaignId) {
   $productId=Campaign::whereKey($campaignId)->value('product_id');
   if (!$productId) abort(404);
   \App\Models\Product::whereKey($productId)->lockForUpdate()->firstOrFail();
   $campaign=Campaign::whereKey($campaignId)->lockForUpdate()->firstOrFail();
   if (!in_array($campaign->status, ['active','draft'], true))
    return ['success'=>false,'message'=>'Only active or draft campaigns can be cancelled'];
   if (DB::table('orders')->where('campaign_id',$campaignId)->exists())
    return ['success'=>false,'message'=>'Campaign has orders and cannot be cancelled'];
   $campaign->participants()->where('status','joined')->update(['status'=>'cancelled']);
   $campaign->update(['status'=>'cancelled','current_participants'=>0]);
   return ['success'=>true,'message'=>'Campaign cancelled; unpurchased reservations released','campaign'=>$campaign->fresh()];
  },3);
 }

 /** Update overdue active campaigns and release only unpurchased reservations. */
 public function expireOverdueCampaigns(): int {
  $ids=Campaign::where('status','active')->where('end_at','<=',now())->pluck('id');
  $expired=0;
  foreach ($ids as $id) {
   $changed=DB::transaction(function () use ($id) {
    $productId=Campaign::whereKey($id)->value('product_id');
    if (!$productId) return false;
    \App\Models\Product::whereKey($productId)->lockForUpdate()->firstOrFail();
    $campaign=Campaign::whereKey($id)->lockForUpdate()->first();
    if (!$campaign || $campaign->status!=='active' || $campaign->end_at->isFuture()) return false;
    // Orders must never be invalidated by a housekeeping task.
    if (DB::table('orders')->where('campaign_id',$id)->exists()) return false;
    $campaign->participants()->where('status','joined')->update(['status'=>'cancelled']);
    $campaign->update(['status'=>'expired','current_participants'=>0]);
    return true;
   },3);
   if ($changed) $expired++;
  }
  return $expired;
 }

}
