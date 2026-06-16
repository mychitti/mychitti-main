<?php

namespace App\Models;

use App\Modules\School\Concerns\BranchScoped;
use Illuminate\Database\Eloquent\Model;

class StudentShortLeave extends Model
{
    use BranchScoped;

    protected $table = 'student_short_leaves';

    protected $fillable = [
        'store_id', 'branch_id', 'student_id', 'academic_session_id',
        'gate_pass_no', 'leave_date', 'out_time', 'return_time', 'is_returning',
        'reason', 'taken_by', 'taken_by_relation', 'contact',
        'issued_by', 'status',
    ];

    protected $casts = [
        'leave_date'   => 'date',
        'is_returning' => 'boolean',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }
}
