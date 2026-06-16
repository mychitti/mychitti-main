<?php

namespace App\Models;

use App\Modules\School\Concerns\BranchScoped;
use Illuminate\Database\Eloquent\Model;

class StudentEnrollment extends Model
{
    use BranchScoped;

    protected $fillable = [
        'store_id', 'branch_id', 'student_id', 'academic_session_id',
        'school_class_id', 'class_section_id', 'roll_no', 'status',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function schoolClass()
    {
        return $this->belongsTo(SchoolClass::class, 'school_class_id');
    }

    public function section()
    {
        return $this->belongsTo(ClassSection::class, 'class_section_id');
    }

    public function session()
    {
        return $this->belongsTo(AcademicSession::class, 'academic_session_id');
    }
}
