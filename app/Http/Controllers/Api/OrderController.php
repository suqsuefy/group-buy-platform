<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class OrderController extends Controller {
 public function __construct(private \App\Services\OrderFulfillmentService $fulfillment) {}

 public function store(Request $request) {
  $data=$request->validate([
   'campaign_id'=>'required|exists:campaigns,id',
   'payment_method'=>'required|in:cash_on_delivery',
   'address_id'=>'required|exists:addresses,id',
  ]);
  $user=$request->user();
  abort_unless(DB::table('addresses')->where('id',$data['address_id'])->where('user_id',$user->id)->exists(),403);
  return DB::transaction(function() use($data,$user){
   $productId=Campaign::whereKey($data['campaign_id'])->value('product_id');
   $product=Product::whereKey($productId)->lockForUpdate()->firstOrFail();
   $c=Campaign::whereKey($data['campaign_id'])->lockForUpdate()->firstOrFail();
   abort_unless($c->status==='fulfilled',422,'Campaign is not confirmed');
   $p=$c->participants()->where('user_id',$user->id)->where('status','joined')->first();
   abort_unless($p,403,'Join campaign first');
   abort_if(Order::where('campaign_id',$c->id)->where('user_id',$user->id)->exists(),422,'Order already placed');
   abort_if($product->stock<$p->quantity,422,'Out of stock');
   $total=round((float)$c->group_price*$p->quantity,2);
   $order=Order::create(['user_id'=>$user->id,'store_id'=>$c->store_id,'campaign_id'=>$c->id,'total'=>$total,'commission'=>round($total*.1,2),'payment_method'=>'cash_on_delivery','status'=>'pending','address_id'=>$data['address_id']]);
   OrderItem::create(['order_id'=>$order->id,'product_id'=>$product->id,'quantity'=>$p->quantity,'unit_price'=>$c->group_price,'total_price'=>$total]);
   $product->decrement('stock',$p->quantity);
   return response()->json(['message'=>'Order created','order'=>$order->load('items')],201);
  });
 }
 public function myOrders(Request $request) {
  return response()->json(Order::with('items.product')->where('user_id',$request->user()->id)->latest()->get());
 }
 public function transition(Request $request, $id) {
  $data=$request->validate([
   'action'=>'required|in:process,ship,deliver,cancel',
   'delivery_reference'=>'required_if:action,ship|nullable|string|max:120',
   'reason'=>'nullable|string|max:255',
  ]);
  $order=Order::with('store')->findOrFail($id);
  $user=$request->user();
  abort_unless($user->status==='active' && ($user->role==='admin' || $order->store?->user_id===$user->id),403);
  $result=$this->fulfillment->transition((int)$id,$data['action'],$data['delivery_reference']??null,$data['reason']??null,$user->id);
  return response()->json($result,$result['success']?200:422);
 }

 public function vendorOrders(Request $request) {
  abort_unless($request->user()->status==='active',403);
  $storeIds=\App\Models\Store::where('user_id',$request->user()->id)->pluck('id');
  return response()->json(Order::with(['items.product','user'])->whereIn('store_id',$storeIds)->latest()->paginate(20));
 }

 public function history(Request $request,$id) {
  $order=Order::with('store')->findOrFail($id);
  $actor=$request->user();
  abort_unless($actor->status==='active' && (
   $actor->role==='admin' || $order->user_id===$actor->id || $order->store?->user_id===$actor->id
  ),403);
  return response()->json(DB::table('order_events')
   ->where('order_id',$order->id)->orderBy('id')->get());
 }

 public function collectCod(Request $request,$id) {
  abort_unless($request->user()->status==='active' && $request->user()->role==='admin',403);
  $result=$this->fulfillment->collectCod((int)$id,$request->user()->id);
  return response()->json($result,$result['success']?200:422);
 }

}
