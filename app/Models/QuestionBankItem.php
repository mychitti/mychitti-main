<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuestionBankItem extends Model
{
    protected $table = 'question_bank';

    protected $fillable = [
        'store_id', 'school_class_id', 'subject_id', 'chapter',
        'question_type', 'difficulty', 'marks', 'question_text', 
        'options', 'answer', 'status', 'created_by',
    ];

    protected $casts = [
        'options' => 'array',
        'marks'   => 'float',
        'status'  => 'boolean',
    ];

    const TYPES  = ['MCQ', 'Short Answer', 'Long Answer', 'True/False', 'Fill in the Blank'];
    const LEVELS = ['Easy', 'Medium', 'Hard'];

    public function schoolClass()
    {
        return $this->belongsTo(SchoolClass::class, 'school_class_id');
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }
}
