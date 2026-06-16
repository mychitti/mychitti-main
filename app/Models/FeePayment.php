<?php

namespace App\Models;

use App\Modules\School\Concerns\BranchScoped;
use Illuminate\Database\Eloquent\Model;

class FeePayment extends Model
{
    use BranchScoped;

    protected $fillable = [
        'store_id', 'branch_id', 'fee_invoice_id', 'student_id',
        'receipt_no', 'amount', 'payment_mode', 'paid_on', 'collected_by',
    ];

    protected $casts = [
        'amount'  => 'float',
        'paid_on' => 'date',
    ];

    public function invoice()
    {
        return $this->belongsTo(FeeInvoice::class, 'fee_invoice_id');
    }

    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }
}
