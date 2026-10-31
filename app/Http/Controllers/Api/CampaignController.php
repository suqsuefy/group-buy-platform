<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\Product;
use App\Models\Store;
use App\Services\CampaignService;
use Illuminate\Http\Request;
class CampaignController extends Controller {
 public function __construct(protected CampaignService $campaignService){}
 public function index() {
  return response()->json(Campaign::with(['store','product'])->where('status','active')->where('start_at','<=',now())->where('end_at','>',now())->whereHas('store',fn($q)=>$q->where('status','active'))->latest()->get());
 }
 public function show($id) {
  return response()->json(Campaign::with(['store','product'])->findOrFail($id));
 }
 public function store(Request $request) {
  $d=$request->validate(['store_id'=>'required|exists:stores,id','product_id'=>'required|exists:products,id','title'=>'required|string|max:255','description'=>'nullable|string','regular_price'=>'required|numeric|gt:0','group_price'=>'required|numeric|gt:0|lt:regular_price','min_participants'=>'required|integer|min:2','target_participants'=>'required|integer|gte:min_participants','start_at'=>'required|date','end_at'=>'required|date|after:start_at|after:now','city'=>'nullable|string']);
  $store=Store::findOrFail($d['store_id']);
  abort_unless($store->user_id===$request->user()->id && $store->status==='active',403);
  $product=Product::findOrFail($d['product_id']);
  abort_unless($product->store_id===$store->id && $product->status==='active',422);
  abort_if($d['target_participants']>$product->stock,422,'Insufficient stock');
  abort_unless((float)$d['regular_price']===(float)$product->regular_price,422,'Regular price must match product');
  $c=Campaign::create([...$d,'status'=>'active','current_participants'=>0]);
  return response()->json(['campaign'=>$c],201);
 }
 public function join(Request $request,$id) {
  $d=$request->validate(['quantity'=>'nullable|integer|min:1']);
  $r=$this->campaignService->joinCampaign((int)$id,$request->user()->id,(int)($d['quantity']??1));
  return response()->json($r,$r['success']?200:422);
 }
 public function confirm(Request $request,$id) {
  $c=Campaign::findOrFail($id);
  abort_unless($request->user()->role==='admin'||$c->store->user_id===$request->user()->id,403);
  $r=$this->campaignService->confirmCampaign((int)$id);
  return response()->json($r,$r['success']?200:422);
 }
}
