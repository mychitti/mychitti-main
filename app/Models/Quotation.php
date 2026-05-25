<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Quotation extends Model
{ 
    use HasFactory;
    protected $fillable = [
        'pdf', 'sales_query_id',
    ];
    public function quote_detail()
    {
        return $this->hasOne(QuotationDetail::class, 'quotation_id');
    }
    public function client()
    {
        return $this->belongsTo(User::class, 'client_name');
    }
    public function storeCustomer()
    {
        return $this->belongsTo(StoreCustomer::class, 'client_name');
    }
    public function salesQuery()
    {
        return $this->belongsTo(\App\Modules\SalesCRM\Models\SalesQuery::class, 'sales_query_id');
    }
}
