<?php

namespace App\Models;

use App\Modules\School\Concerns\BranchScoped;
use Illuminate\Database\Eloquent\Model;

class StudentLeave extends Model
{
    use BranchScoped;

    protected $table = 'student_leaves';

    protected $fillable = [
        'store_id', 'branch_id', 'student_id', 'academic_session_id',
        'leave_type', 'from_date', 'to_date', 'days', 'reason',
        'status', 'applied_by', 'reviewed_by', 'remarks',
    ];

    protected $casts = [
        'from_date' => 'date',
        'to_date'   => 'date',
        'days'      => 'integer',
    ];

    const TYPES = ['Sick', 'Casual', 'Family', 'Other'];

    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }
}
