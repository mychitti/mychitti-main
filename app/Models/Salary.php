<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Salary extends Model
{
    use HasFactory;
    protected $fillable = [
        'vendor_id',
        'employee_id',
        'base_salary',
        'payable_salary',
        'total_payable',
        'salary_month',
        'bonus_incentives',
        'allowance_amount',
        'allowance',
        'deductions',
        'advance_payment_deductions',
        'generated_at',
        'created_at',
    ];
    public function employee()
    {
        return $this->belongsTo(VendorEmployee::class, 'employee_id');
    }
}
