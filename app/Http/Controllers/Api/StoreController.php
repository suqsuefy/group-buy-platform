<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class StoreController extends Controller
{
    public function index() {
        return response()->json(Store::where('status','active')->get());
    }
    public function store(Request $request) {
        $data=$request->validate([
            'name'=>'required|string|max:255',
            'description'=>'nullable|string',
            'phone'=>'nullable|string',
            'city'=>'nullable|string',
        ]);
        $store=Store::create([
            ...$data,
            'user_id'=>$request->user()->id,
            'slug'=>Str::slug($data['name']).'-'.Str::lower(Str::random(8)),
            'status'=>'pending',
        ]);
        return response()->json(['message'=>'Store submitted for review','store'=>$store],201);
    }
}
