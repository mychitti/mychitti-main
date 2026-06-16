<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MarkEntry extends Model
{
    protected $fillable = [
        'store_id', 'exam_id', 'exam_subject_id', 'student_id', 'marks_obtained', 'is_absent',
    ];

    protected $casts = [
        'marks_obtained' => 'float',
        'is_absent'      => 'boolean',
    ];

    public function exam()
    {
        return $this->belongsTo(Exam::class, 'exam_id');
    }

    public function examSubject()
    {
        return $this->belongsTo(ExamSubject::class, 'exam_subject_id');
    }
}
