<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
 
class ReceivableReceipt extends Model
{
    use HasFactory;

    public function client()
    {
        return $this->belongsTo(StoreCustomer::class, 'client_id');
    }
    public function employee()
    {
        return $this->belongsTo(VendorEmployee::class, 'employee_id');
    }
   
}
