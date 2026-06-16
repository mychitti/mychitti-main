<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Exam extends Model
{
    protected $table = 'school_exams';

    protected $fillable = [
        'store_id', 'branch_id', 'academic_session_id', 'school_class_id',
        'name', 'exam_type', 'status',
    ];

    const TYPES = ['Unit Test', 'Quarterly', 'Mid-Term', 'Half-Yearly', 'Annual'];

    public function schoolClass()
    {
        return $this->belongsTo(SchoolClass::class, 'school_class_id');
    }

    public function subjects()
    {
        return $this->hasMany(ExamSubject::class, 'exam_id');
    }
}
