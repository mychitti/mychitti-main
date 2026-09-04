<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentationFile extends Model
{
    protected $fillable = [
        'documentation_id', 'file_name', 'stored_name', 'extension', 'mime', 'size', 'uploaded_by',
    ];

    public function documentation()
    {
        return $this->belongsTo(Documentation::class, 'documentation_id');
    }

    public function getReadableSizeAttribute(): string
    {
        return _documentationReadableSize($this->size);
    }
}
