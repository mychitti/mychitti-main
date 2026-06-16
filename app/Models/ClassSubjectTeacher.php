<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClassSubjectTeacher extends Model
{
    protected $fillable = [
        'store_id', 'branch_id', 'school_class_id', 'class_section_id', 'subject_id', 'teacher_emp_id',
    ];

    public function schoolClass()
    {
        return $this->belongsTo(SchoolClass::class, 'school_class_id');
    }

    public function section()
    {
        return $this->belongsTo(ClassSection::class, 'class_section_id');
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }

    public function teacher()
    {
        return $this->belongsTo(VendorEmployee::class, 'teacher_emp_id');
    }
}
