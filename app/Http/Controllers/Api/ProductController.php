<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    public function index() {
        return response()->json(Product::where('status','active')->whereHas('store', fn($q) => $q->where('status','active'))->get());
    }
    public function show($id) {
        return response()->json(Product::where('status','active')->whereHas('store', fn($q) => $q->where('status','active'))->findOrFail($id));
    }
    public function store(Request $request) {
        $data = $request->validate([
            'store_id'=>'required|exists:stores,id',
            'category_id'=>'nullable|exists:categories,id',
            'title'=>'required|string|max:255',
            'description'=>'nullable|string',
            'short_description'=>'nullable|string',
            'regular_price'=>'required|numeric|gt:0',
            'sale_price'=>'nullable|numeric|gt:0|lte:regular_price',
            'stock'=>'required|integer|min:0',
            'image'=>'nullable|string',
        ]);
        $store = Store::findOrFail($data['store_id']);
        abort_unless($store->user_id === $request->user()->id && $store->status === 'active', 403);
        $product = Product::create([
            ...$data,
            'slug'=>Str::slug($data['title']).'-'.Str::lower(Str::random(8)),
            'status'=>'active',
        ]);
        return response()->json(['message'=>'Product created successfully','product'=>$product],201);
    }
}
