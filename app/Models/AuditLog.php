<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
 
class AuditLog extends Model
{
    use HasFactory;

    public function employee(){
        return $this->belongsTo(VendorEmployee::class , 'created_by');
    }
    public function adminEmployee(){
        return $this->belongsTo(Admin::class , 'created_by');
    }
}
