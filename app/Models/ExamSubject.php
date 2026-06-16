<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExamSubject extends Model
{
    protected $fillable = [
        'store_id', 'exam_id', 'subject_id', 'max_marks', 'pass_marks',
    ];

    protected $casts = [
        'max_marks'  => 'float',
        'pass_marks' => 'float',
    ];

    public function subject()
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }

    public function exam()
    {
        return $this->belongsTo(Exam::class, 'exam_id');
    }
}
