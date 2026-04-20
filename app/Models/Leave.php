<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Leave extends Model
{
    use HasFactory;
    protected $fillable = [
        'vendor_id',
        'emp_id',
        'employee_type',
        'leave_date',
        'day',
        'month',
        'year',
        'leave_type',
        'reason',
        'status',
        'added_by',
        'created_at',
    ];
    public function employee()
    {
        return $this->belongsTo(VendorEmployee::class, 'emp_id');
    }
}
