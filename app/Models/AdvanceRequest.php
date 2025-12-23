<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdvanceRequest extends Model
{
    protected $fillable = [
        'employee_id',
        'requested_amount',
        'approved_amount',
        'status',
        'reason',
        'repayment_start_date',
        'installments'
    ];

    public function employee() 
    {
        return $this->belongsTo(VendorEmployee::class);
    }
}
