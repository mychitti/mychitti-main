<?php

namespace App\Models;

use App\Modules\School\Concerns\BranchScoped;
use Illuminate\Database\Eloquent\Model;

class SchoolHomeworkSubmission extends Model
{ 
    use BranchScoped;

    protected $table = 'school_homework_submissions';

    protected $fillable = [
        'store_id',
        'branch_id',
        'school_homework_id',
        'student_id',
        'submission_date',
        'student_notes',
        'attachment',
        'status',
        'marks_obtained',
        'remarks',
        'evaluated_by',
        'evaluated_at',
    ];

    protected $casts = [
        'submission_date' => 'date',
        'marks_obtained'  => 'float',
        'evaluated_at'    => 'datetime',
    ];

    const STATUSES = [
        'submitted' => 'Submitted',
        'evaluated' => 'Evaluated',
        'late'      => 'Late Submission',
        'resubmit'  => 'Need Resubmission',
    ];

    public function homework()
    {
        return $this->belongsTo(SchoolHomework::class, 'school_homework_id');
    }

    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }
}
