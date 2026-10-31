<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Store extends Model
{
    protected $fillable = ['user_id','name','slug','description','logo','phone','city','status'];

    public function owner() { return $this->belongsTo(User::class, 'user_id'); }
    public function products() { return $this->hasMany(Product::class); }
    public function campaigns() { return $this->hasMany(Campaign::class); }
}
