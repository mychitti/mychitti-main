<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApiProject extends Model
{
    protected $fillable = [
        'name', 'slug', 'base_url', 'version', 'color', 'description', 'status', 'created_by',
    ];

    public function endpoints()
    {
        return $this->hasMany(ApiEndpoint::class, 'project_id')
            ->orderBy('folder')->orderBy('sort_order')->orderBy('id');
    }

    public function author()
    {
        return $this->belongsTo(Admin::class, 'created_by');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }
}
