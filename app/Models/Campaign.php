<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Campaign extends Model
{
    protected $fillable = [
        'store_id',
        'product_id',
        'title',
        'description',
        'regular_price',
        'group_price',
        'min_participants',
        'current_participants',
        'target_participants',
        'start_at',
        'end_at',
        'city',
        'status',
    ];
}
