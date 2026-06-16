<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClassSection extends Model
{
    protected $fillable = [
        'store_id', 'branch_id', 'school_class_id', 'name', 'capacity', 'class_teacher_emp_id', 'status',
    ];

    public function schoolClass()
    {
        return $this->belongsTo(SchoolClass::class, 'school_class_id');
    }

    public function classTeacher()
    {
        return $this->belongsTo(VendorEmployee::class, 'class_teacher_emp_id');
    }
}
