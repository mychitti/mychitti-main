<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TimetableEntry extends Model
{
    protected $table = 'timetable_entries';

    protected $fillable = [
        'store_id', 'branch_id', 'school_class_id', 'class_section_id',
        'day_of_week', 'timetable_period_id', 'subject_id', 'teacher_emp_id',
    ];

    public const DAYS = [
        1 => 'Monday',
        2 => 'Tuesday',
        3 => 'Wednesday',
        4 => 'Thursday',
        5 => 'Friday',
        6 => 'Saturday',
    ];

    public function schoolClass()
    {
        return $this->belongsTo(SchoolClass::class, 'school_class_id');
    }

    public function section()
    {
        return $this->belongsTo(ClassSection::class, 'class_section_id');
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }

    public function teacher()
    {
        return $this->belongsTo(VendorEmployee::class, 'teacher_emp_id');
    }

    public function period()
    {
        return $this->belongsTo(TimetablePeriod::class, 'timetable_period_id');
    }
}
