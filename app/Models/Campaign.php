<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Campaign extends Model
{
    protected $fillable = ['store_id','product_id','title','description','regular_price','group_price','min_participants','current_participants','target_participants','start_at','end_at','city','status'];
    protected $casts = ['start_at' => 'datetime', 'end_at' => 'datetime'];

    public function store() { return $this->belongsTo(Store::class); }
    public function product() { return $this->belongsTo(Product::class); }
    public function participants() { return $this->hasMany(CampaignParticipant::class); }
}
