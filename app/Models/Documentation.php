<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Documentation extends Model
{
    protected $fillable = [
        'title', 'slug', 'category_id', 'doc_type', 'summary', 'content',
        'version', 'tags', 'status', 'created_by', 'updated_by',
    ];

    const DOC_TYPES = [
        'editor' => 'Written Document',
        'file'   => 'Uploaded File',
    ];

    public function category()
    {
        return $this->belongsTo(DocumentationCategory::class, 'category_id');
    }

    public function files()
    {
        return $this->hasMany(DocumentationFile::class, 'documentation_id')->latest('id');
    }

    public function versions()
    {
        return $this->hasMany(DocumentationVersion::class, 'documentation_id')->latest('id');
    }

    public function author()
    {
        return $this->belongsTo(Admin::class, 'created_by');
    }

    public function editor()
    {
        return $this->belongsTo(Admin::class, 'updated_by');
    }

    public function getTagListAttribute(): array
    {
        return array_values(array_filter(array_map('trim', explode(',', (string) $this->tags))));
    }

    /**
     * Next version label. "1.4" becomes "1.5"; anything unparseable restarts at 1.0.
     */
    public function nextVersion(): string
    {
        if (preg_match('/^(\d+)\.(\d+)$/', (string) $this->version, $m)) {
            return $m[1] . '.' . ((int) $m[2] + 1);
        }
        return '1.0';
    }
}
