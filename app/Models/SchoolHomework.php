<?php

namespace App\Models;

use App\Modules\School\Concerns\BranchScoped;
use Illuminate\Database\Eloquent\Model;

class SchoolHomework extends Model
{ 
    use BranchScoped;

    protected $table = 'school_homeworks';

    protected $fillable = [ 
        'store_id',
        'branch_id',
        'academic_session_id',
        'school_class_id',
        'class_section_id',
        'subject_id',
        'title',
        'description',
        'assign_date',
        'submission_date',
        'max_marks',
        'attachment',
        'created_by',
    ];

    protected $casts = [
        'assign_date'     => 'date',
        'submission_date' => 'date',
        'max_marks'       => 'float',
    ];

    public function schoolClass()
    {
        return $this->belongsTo(SchoolClass::class, 'school_class_id');
    }

    public function classSection()
    {
        return $this->belongsTo(ClassSection::class, 'class_section_id');
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }

    public function submissions()
    {
        return $this->hasMany(SchoolHomeworkSubmission::class, 'school_homework_id');
    }
}
