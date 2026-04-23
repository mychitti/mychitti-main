<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
 
class VendorLoginLog extends Model
{
    protected $fillable = [
        'vendor_id',
        'login_at',
        'last_activity_at'
    ];

    public function vendor(){
        return $this->belongsTo(Vendor::class);
    }
}
