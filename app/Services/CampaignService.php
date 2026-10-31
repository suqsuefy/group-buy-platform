<?php
namespace App\Services;
use App\Models\Campaign;
use App\Models\CampaignParticipant;
use Illuminate\Support\Facades\DB;
class CampaignService {
 public function joinCampaign(int $campaignId,int $userId,int $quantity=1,?string $idempotencyKey=null):array {
  return DB::transaction(function()use($campaignId,$userId,$quantity,$idempotencyKey){
   if ($idempotencyKey !== null) {
    $existing=DB::table('campaign_join_requests')->where('campaign_id',$campaignId)->where('user_id',$userId)->where('idempotency_key',$idempotencyKey)->first();
    if ($existing) {
     if ((int)$existing->quantity !== $quantity) return ['success'=>false,'message'=>'Idempotency key reused with different quantity'];
     return ['success'=>true,'message'=>'Previously joined (retry)','replayed'=>true];
    }
   }
   $c=Campaign::whereKey($campaignId)->lockForUpdate()->firstOrFail();
   if($c->status!=='active'||now()->lt($c->start_at)||now()->gte($c->end_at))
    return ['success'=>false,'message'=>'Campaign is not open'];
   if($quantity<1) return ['success'=>false,'message'=>'Invalid quantity'];
   $p=CampaignParticipant::where('campaign_id',$campaignId)->where('user_id',$userId)->first();
   if($p && $p->status==='cancelled') $p->quantity=0;
   $count=$c->participants()->where('status','joined')->where('user_id','!=',$userId)->count();
   $totalUnits=(int)$c->participants()->where('status','joined')->where('user_id','!=',$userId)->sum('quantity')+($p?$p->quantity:0)+$quantity;
   if($count+1>$c->target_participants || $totalUnits>$c->product->stock)
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
}
