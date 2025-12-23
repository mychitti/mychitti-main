<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StorageUnit extends Model
{
    use HasFactory;
    public function parent()
    {
        return $this->belongsTo(StorageUnit::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(StorageUnit::class, 'parent_id');
    }
    public function getFullHierarchyNameAttribute()
{
    $names = [$this->name];
    $current = $this->parent;

    while ($current) {
        array_unshift($names, $current->name); // add to the beginning
        $current = $current->parent;
    }

    return implode(' - ', $names);
}
}
