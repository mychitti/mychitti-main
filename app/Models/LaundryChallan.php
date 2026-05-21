<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LaundryChallan extends Model
{
    use HasFactory;

    protected $fillable = [
        'store_id',
        'challan_no',
        'store_customer_id',
        'outward_date',
        'expected_inward_date',
        'inward_date',
        'status',
        'invoice_id',
        'notes',
    ];

    protected $casts = [
        'outward_date'         => 'date',
        'expected_inward_date' => 'date',
        'inward_date'          => 'date',
    ];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function hotel()
    {
        return $this->belongsTo(StoreCustomer::class, 'store_customer_id');
    }

    public function items()
    {
        return $this->hasMany(LaundryChallanItem::class, 'laundry_challan_id');
    }

    public function invoice()
    {
        return $this->belongsTo(ManualInvoice::class, 'invoice_id', 'invoice_id');
    }

    public function getStatusLabelAttribute()
    {
        return match($this->status) {
            'pending'  => 'Pending',
            'partial'  => 'Partial Return',
            'returned' => 'Returned',
            default    => ucfirst($this->status),
        };
    }
}
