<?php

namespace App\Models;

use App\Modules\School\Concerns\BranchScoped;
use Illuminate\Database\Eloquent\Model;

class SchoolHostelRoom extends Model
{
    use BranchScoped;

    protected $table = 'school_hostel_rooms';

    protected $fillable = [
        'store_id', 'branch_id', 'school_hostel_block_id',
        'room_no', 'floor', 'capacity', 'rent', 'status',
    ];

    protected $casts = [
        'rent' => 'float',
    ];

    public function block()
    {
        return $this->belongsTo(SchoolHostelBlock::class, 'school_hostel_block_id');
    }

    public function allocations()
    {
        return $this->hasMany(SchoolHostelAllocation::class, 'school_hostel_room_id');
    }
}
