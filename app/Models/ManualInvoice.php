<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Patient;

/** @mixin \Eloquent */
class ManualInvoice extends Model
{
    use HasFactory;

    protected $fillable = [ 
        'bill_to',
        'bill_to_type',
        'user_type', 
        'invoice_id',
        'invoice_serial',
        'type',
        'module_id',
        'total_amount',
        'payment_method',
        'vendor_id',
        'invoice_date',
        'payment_status',
        'payment_date',
        'tax_type',
        'reminder_date',
        'reminder_freq_unit',
        'reminder_freq',
        'reminder_status',
        'pdf',
        'reference_number',
        'bill_gst_type',
        'cgst',
        'sgst',
        'igst',
        'taxable_amount',
        'final_tax',
        'cash_amount',
        'online_amount',
        'meta',
        'financial_year',
        'subtotal_amount',
        'round_off',
    ];
    protected $casts = [
        'reference_number' => 'array',
        'meta'             => 'array',
    ];

    public function store()
    {
        return $this->belongsTo(Store::class, 'vendor_id');
    }
    public function websiteUser()
    {
        return $this->belongsTo(User::class, 'bill_to');
    }
    public function websiteVendor()
    {
        return $this->belongsTo(Store::class, 'bill_to');
    }

    public function storeCustomer()
    {
        return $this->belongsTo(StoreCustomer::class, 'bill_to');
    }
    public function seller()
    {
        return $this->belongsTo(StoreCustomer::class, 'store_vendor_id');
    }
    public function invoiceItems()
    {
        return $this->hasMany(InvoiceItem::class, 'manual_invoice_id');
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class, 'bill_to');
    }

    public function getUserAttribute()
    {
        if ($this->bill_to_type === 'patient') {
            return $this->patient;
        }
        if ($this->user_type === 'website_user') {
            return $this->websiteUser;
        } elseif ($this->user_type === 'store_user') {
            return $this->storeCustomer;
        } elseif ($this->user_type === 'store_vendor') {
            return $this->storeCustomer;
        }
        return null;
    }
    public function bankAccount()
    {
        return $this->belongsTo(AccountDetail::class, 'bank_account');
    }
}
