<?php

namespace App\Models;

use App\Modules\School\Concerns\BranchScoped;
use Illuminate\Database\Eloquent\Model;

class FeeInvoice extends Model
{
    use BranchScoped;

    protected $fillable = [
        'store_id', 'branch_id', 'student_id', 'academic_session_id',
        'invoice_no', 'invoice_date', 'items', 'total_amount', 'concession', 'concession_note',
        'gst_amount', 'paid_amount', 'due_amount', 'status',
    ];

    // items: [{head_id, name, amount, gst_percent, gst_amount}]
    protected $casts = [
        'items'        => 'array',
        'invoice_date' => 'date',
        'total_amount' => 'float',
        'concession'   => 'float',
        'gst_amount'   => 'float',
        'paid_amount'  => 'float',
        'due_amount'   => 'float',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function payments()
    {
        return $this->hasMany(FeePayment::class, 'fee_invoice_id');
    }
}
