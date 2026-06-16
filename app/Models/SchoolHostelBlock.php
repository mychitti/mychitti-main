<?php

namespace App\Models;

use App\Modules\School\Concerns\BranchScoped;
use Illuminate\Database\Eloquent\Model;

class SchoolHostelBlock extends Model
{
    use BranchScoped;

    protected $table = 'school_hostel_blocks';

    protected $fillable = [
        'store_id', 'branch_id', 'name', 'type',
        'warden_name', 'warden_phone', 'status',
    ];

    public const TYPES = ['boys' => 'Boys', 'girls' => 'Girls', 'mixed' => 'Mixed'];

    public function rooms()
    {
        return $this->hasMany(SchoolHostelRoom::class, 'school_hostel_block_id');
    }

    public function allocations()
    {
        return $this->hasMany(SchoolHostelAllocation::class, 'school_hostel_block_id');
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->type] ?? ucfirst((string) $this->type);
    }
}
