<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuotationDetail extends Model
{
    use HasFactory;
       protected $fillable = [
        'pdf', 'cash_amount', 'online_amount',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'bill_to');
    }
    public function store()
    {
        return $this->belongsTo(Store::class, 'vendor_id');
    }
}
