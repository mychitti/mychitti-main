<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentationCategory extends Model
{
    protected $fillable = ['name', 'slug', 'color', 'description', 'sort_order', 'status'];

    public function documents()
    {
        return $this->hasMany(Documentation::class, 'category_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }
}
