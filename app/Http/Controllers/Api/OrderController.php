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
 public function store(Request $request) {
  $data=$request->validate([
   'campaign_id'=>'required|exists:campaigns,id',
   'payment_method'=>'required|in:cash_on_delivery',
   'address_id'=>'required|exists:addresses,id',
  ]);
  $user=$request->user();
  abort_unless(DB::table('addresses')->where('id',$data['address_id'])->where('user_id',$user->id)->exists(),403);
  return DB::transaction(function() use($data,$user){
   $c=Campaign::whereKey($data['campaign_id'])->lockForUpdate()->firstOrFail();
   abort_unless($c->status==='fulfilled',422,'Campaign is not confirmed');
   $p=$c->participants()->where('user_id',$user->id)->where('status','joined')->first();
   abort_unless($p,403,'Join campaign first');
   abort_if(Order::where('campaign_id',$c->id)->where('user_id',$user->id)->exists(),422,'Order already placed');
   $product=Product::whereKey($c->product_id)->lockForUpdate()->firstOrFail();
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
}
