<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class BlogPost extends Model
{
    use HasFactory;

    protected $fillable = [
        'title', 'slug', 'image', 'category_id', 'description', 'short_description',
        'type', 'status', 'store_id', 'posted_by',
    ];

    public function scopeActive($query)
    {
        return $query->where('status', '1');
    }
    public function getImageUrlAttribute()
    {
        return $this->image ? asset('storage/app/public/blog_content_images/' . $this->image) : null;
    }
}
