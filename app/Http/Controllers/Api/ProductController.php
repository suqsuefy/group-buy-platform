<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    public function index()
    {
        return response()->json(Product::all());
    }

    public function show($id)
    {
        $product = Product::findOrFail($id);

        return response()->json($product);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'store_id' => 'required|exists:stores,id',
            'category_id' => 'nullable|exists:categories,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'short_description' => 'nullable|string',
            'regular_price' => 'required|numeric',
            'sale_price' => 'nullable|numeric',
            'stock' => 'required|integer|min:0',
            'image' => 'nullable|string',
        ]);

        $product = Product::create([
            'store_id' => $data['store_id'],
            'category_id' => $data['category_id'] ?? null,
            'title' => $data['title'],
            'slug' => Str::slug($data['title']),
            'description' => $data['description'] ?? null,
            'short_description' => $data['short_description'] ?? null,
            'regular_price' => $data['regular_price'],
            'sale_price' => $data['sale_price'] ?? null,
            'stock' => $data['stock'],
            'image' => $data['image'] ?? null,
            'status' => 'active',
        ]);

        return response()->json([
            'message' => 'Product created successfully',
            'product' => $product,
        ], 201);
    }
}
