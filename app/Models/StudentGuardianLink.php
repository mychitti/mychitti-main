<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentGuardianLink extends Model
{
    protected $table = 'student_guardian_links';

    protected $fillable = ['user_id', 'student_id', 'store_id'];

    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }
}
