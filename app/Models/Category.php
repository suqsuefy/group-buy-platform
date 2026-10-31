<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Category extends \Illuminate\Database\Eloquent\Model
{
    protected $fillable = ['name', 'slug', 'parent_id'];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }
}
