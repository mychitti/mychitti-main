<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LaundryOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'store_id',
        'order_no',
        'store_customer_id',
        'customer_name',
        'customer_phone',
        'drop_date',
        'expected_ready_date',
        'pickup_date',
        'status',
        'total_amount',
        'payment_status',
        'employee_id',
        'receivable_receipt_id',
        'invoice_id',
        'notes',
    ];

    protected $casts = [
        'drop_date'           => 'date',
        'expected_ready_date' => 'date',
        'pickup_date'         => 'date',
        'total_amount'        => 'float',
    ];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function customer()
    {
        return $this->belongsTo(StoreCustomer::class, 'store_customer_id');
    }

    public function employee()
    {
        return $this->belongsTo(VendorEmployee::class, 'employee_id');
    }

    public function items()
    {
        return $this->hasMany(LaundryOrderItem::class);
    }

    public function invoice()
    {
        return $this->belongsTo(ManualInvoice::class, 'invoice_id', 'invoice_id');
    }

    public function receipt()
    {
        return $this->belongsTo(ReceivableReceipt::class, 'receivable_receipt_id');
    }

    public function getCustomerDisplayNameAttribute()
    {
        if ($this->customer) {
            return $this->customer->f_name;
        }
        return $this->customer_name ?? 'Guest';
    }

    public function getCustomerDisplayPhoneAttribute()
    {
        if ($this->customer) {
            return $this->customer->phone;
        }
        return $this->customer_phone ?? '-';
    }
}
