<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TempStoreStatus extends Model
{
    use HasFactory;
    public function serviceStatus()
    {
        return $this->belongsTo(ServiceStatus::class, 'status_id');
    }
    public function store()
    {
        return $this->belongsTo(Store::class, 'store_id');
    }
    
}
