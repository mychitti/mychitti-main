<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
 
class RequestForm extends Model
{
    use HasFactory;

    protected $fillable = ['date'];

       public function requestedBy()
    {
        if($this->store_id === 0){
           return $this->belongsTo(Admin::class, 'requested_by');
        }
        return $this->belongsTo(VendorEmployee::class, 'requested_by');
    }
       public function requestedTo()
    {
          if($this->store_id === 0){
           return $this->belongsTo(Admin::class, 'request_to');
        }
        return $this->belongsTo(VendorEmployee::class, 'request_to');
    }
       public function createdBy()
    {
          if($this->store_id == 0){
           return $this->belongsTo(Admin::class, 'created_by');
        }
        return $this->belongsTo(VendorEmployee::class, 'created_by');
    }
       public function forwardedTo()
    {
          if($this->store_id == 0){
           return $this->belongsTo(Admin::class, 'forwarded_to');
        }
        return $this->belongsTo(VendorEmployee::class, 'forwarded_to');
    }
       public function debitAccount()
    {
        return $this->belongsTo(StoreAccount::class, 'debit_account_id');
    }
       public function creditAccount()
    {
        return $this->belongsTo(StoreAccount::class, 'credit_account_id');
    }
    public function statusUpdates(){
        return $this->hasMany(RequestFormUpdate::class, 'request_form_id');
    }
    public function voucher(){
        return $this->belongsTo(StoreVoucher::class, 'request_no');
    }
    public function customer(){
        return $this->belongsTo(StoreCustomer::class, 'store_user');
    }

}
