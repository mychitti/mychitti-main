<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TimetableSubstitution extends Model
{
    protected $table = 'timetable_substitutions';

    protected $fillable = [
        'store_id', 'branch_id', 'sub_date', 'timetable_entry_id',
        'substitute_teacher_emp_id', 'reason',
    ];

    protected $casts = [
        'sub_date' => 'date',
    ];

    public function entry()
    {
        return $this->belongsTo(TimetableEntry::class, 'timetable_entry_id');
    }

    public function substitute()
    {
        return $this->belongsTo(VendorEmployee::class, 'substitute_teacher_emp_id');
    }
}
