<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class StoreSignature extends Model
{
    use HasFactory;

    public function employee()
    {
        return $this->belongsTo(VendorEmployee::class, 'staff_id');
    }

    public function adminEmployee()
    {
        return $this->belongsTo(Admin::class, 'staff_id');
    }
} 