<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
 
class DayBook extends Model
{
    use HasFactory;

    protected $fillable = [
        'amount',
        'store_id',
        'type',
        'particular',
        'invoice_id',
    ];
    protected $casts = [
        'reference_number' => 'array',
    ];
      public function invoice()
    {
        return $this->belongsTo(ManualInvoice::class, 'invoice_id');
    }

}
