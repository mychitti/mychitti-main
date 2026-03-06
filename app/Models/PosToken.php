<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PosToken extends Model
{
  use HasFactory;
  protected $fillable =[ 
    'payment_status',
  ];

  public function tokenItems() 
  {
    return $this->hasMany(PosTokenItem::class, 'token_id', 'id');
  }
  public function client()
  {
    return $this->belongsTo(StoreCustomer::class, 'customer_id', 'id');
  }
  public function branch()
  {
    return $this->belongsTo(Branch::class, 'branch_id', 'id');
  }
  public function invoice()
  {
    return $this->belongsTo(ManualInvoice::class, 'invoice_table_id', 'id');
  }
  public function upiAccount()
  {
    return $this->belongsTo(AccountDetail::class, 'upi_account_id', 'id');
  }
  public function staff()
  {
    return $this->belongsTo(VendorEmployee::class, 'staff_id', 'id');
  }
}
