<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
 
class RequestFormUpdate extends Model
{
    use HasFactory;

        public function updatedBy()
    {
        return $this->belongsTo(VendorEmployee::class, 'updated_by');
    }
       public function sentTo()
    {
        return $this->belongsTo(VendorEmployee::class, 'sent_to');
    }
  

}
