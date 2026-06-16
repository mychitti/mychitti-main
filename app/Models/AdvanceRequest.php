<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdvanceRequest extends Model
{
    protected $fillable = [
        'employee_id',
        'store_id',
        'requested_amount',
        'approved_amount',
        'status',
        'reason',
        'repayment_start_date',
        'installments',
        'required_on',
        'account_posted',
    ];

    public function employee()
    { 
        if ($this->store_id == 0) {
            return $this->belongsTo(Admin::class, 'employee_id');
        }
        return $this->belongsTo(VendorEmployee::class);
    }
}
