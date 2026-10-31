<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens;

    protected $fillable = ['name','email','phone','password','role','city','avatar','status'];
    protected $hidden = ['password','remember_token'];

    public function stores() { return $this->hasMany(Store::class); }
}
