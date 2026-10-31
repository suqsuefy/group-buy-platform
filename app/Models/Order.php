<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = ['user_id','store_id','campaign_id','total','commission','payment_method','status','address_id'];
    public function items() { return $this->hasMany(OrderItem::class); }
}
