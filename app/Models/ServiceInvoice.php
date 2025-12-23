<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceInvoice extends Model
{

    use HasFactory;
    protected $fillable = [
        'reminded',
        'final_tax',
        'cgst',
        'sgst',
        'igst',
        'taxable_amount',
        'bill_gst_type',
    ];

    public function websiteUser()
    {
        return $this->belongsTo(User::class, 'bill_to');
    }

    public function storeCustomer()
    {
        return $this->belongsTo(StoreCustomer::class, 'bill_to');
    }
    public function invoiceItems()
    {
        return $this->hasMany(InvoiceItem::class, 'rand_invoice_id', 'invoice_id');
    }
    public function getUserAttribute()
    {
        if ($this->user_type === 'website_user') {
            return $this->websiteUser;
        } elseif ($this->user_type === 'store_user') {
            return $this->storeCustomer;
        }
        return null;
    }
    public function service_request()
    {
        return $this->belongsTo(ServiceRequest::class, 'service_id');
    }
    public function store()
    {
        return $this->belongsTo(Store::class, 'vendor_id');
    }
}
