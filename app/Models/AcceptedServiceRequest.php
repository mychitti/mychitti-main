<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AcceptedServiceRequest extends Model
{
    use HasFactory;
    public function store()
    {
        return $this->belongsTo(Store::class, 'vendor_id')->withoutGlobalScopes();
    }
}
