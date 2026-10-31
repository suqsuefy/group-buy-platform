<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'store_id',
        'category_id',
        'title',
        'slug',
        'description',
        'short_description',
        'regular_price',
        'sale_price',
        'stock',
        'image',
        'status',
    ];
}
