<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentationVersion extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'documentation_id', 'version', 'source', 'content', 'file_name',
        'stored_name', 'extension', 'size', 'note', 'created_by', 'created_at',
    ];

    protected $casts = ['created_at' => 'datetime'];

    public function documentation()
    {
        return $this->belongsTo(Documentation::class, 'documentation_id');
    }

    public function author()
    {
        return $this->belongsTo(Admin::class, 'created_by');
    }
}
