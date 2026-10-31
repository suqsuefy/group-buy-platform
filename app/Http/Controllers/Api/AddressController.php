<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\Address;
use Illuminate\Http\Request;
class AddressController extends Controller {
 public function index(Request $request){return response()->json(Address::where('user_id',$request->user()->id)->latest()->get());}
 public function store(Request $request){
  $d=$request->validate(['title'=>'required|string|max:100','city'=>'required|string|max:100','area'=>'required|string|max:100','street'=>'nullable|string|max:255','building'=>'nullable|string|max:100','apartment'=>'nullable|string|max:100','phone'=>'required|string|max:30']);
  $a=Address::create([...$d,'user_id'=>$request->user()->id]);
  return response()->json(['address'=>$a],201);
 }
}
