<?php

namespace App\Models;

use App\Modules\School\Concerns\BranchScoped;
use Illuminate\Database\Eloquent\Model;

class StudentAttendance extends Model
{
    use BranchScoped;

    protected $fillable = [
        'store_id', 'branch_id', 'student_id', 'academic_session_id',
        'school_class_id', 'class_section_id', 'attendance_date', 'status', 'marked_by',
    ];

    protected $casts = [
        'attendance_date' => 'date',
    ];

    const STATUSES = [
        'present'  => 'Present',
        'absent'   => 'Absent',
        'late'     => 'Late',
        'half_day' => 'Half-Day',
        'leave'    => 'Leave',
        'holiday'  => 'Holiday',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }
}
