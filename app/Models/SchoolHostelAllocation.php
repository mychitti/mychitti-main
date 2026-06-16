<?php

namespace App\Models;

use App\Modules\School\Concerns\BranchScoped;
use Illuminate\Database\Eloquent\Model;

class SchoolHostelAllocation extends Model
{
    use BranchScoped;

    protected $table = 'school_hostel_allocations';

    protected $fillable = [
        'store_id', 'branch_id', 'student_id',
        'school_hostel_block_id', 'school_hostel_room_id',
        'allocated_on', 'monthly_fee',
    ];

    protected $casts = [
        'allocated_on' => 'date',
        'monthly_fee'  => 'float',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function block()
    {
        return $this->belongsTo(SchoolHostelBlock::class, 'school_hostel_block_id');
    }

    public function room()
    {
        return $this->belongsTo(SchoolHostelRoom::class, 'school_hostel_room_id');
    }
}
