<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = ['user_id','store_id','campaign_id','total','commission','payment_method','status','address_id'];
    public function items() { return $this->hasMany(OrderItem::class); }
    public function store() { return $this->belongsTo(Store::class); }
    public function user() { return $this->belongsTo(User::class); }
    protected $casts = ['shipped_at'=>'datetime','delivered_at'=>'datetime','cod_collected_at'=>'datetime','cancelled_at'=>'datetime'];
}
