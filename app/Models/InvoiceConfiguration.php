<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvoiceConfiguration extends Model
{
    use HasFactory;
    protected $fillable = ['store_id','invoice_sign_status'];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }
}
