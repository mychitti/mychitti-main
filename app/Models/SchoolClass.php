<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SchoolClass extends Model
{
    protected $table = 'school_classes';

    protected $fillable = [
        'store_id', 'branch_id', 'name', 'numeric_order', 'status',
    ];

    public function sections()
    {
        return $this->hasMany(ClassSection::class, 'school_class_id');
    }
}
