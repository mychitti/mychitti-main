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
    public function staff()
    {
        return $this->morphTo(null, 'assigned_type', 'assigned_to');
    }
    public function serviceRequest()
    {
        return $this->belongsTo(ServiceRequest::class, 'service_request_id');
    }
 
    public function getAssignedDetailsAttribute()
    {
        $model = $this->staff;

        if (!$model) {
            return null;
        }

        if ($model instanceof \App\Models\Store) {

            return [
                'id'      => $model->id,
                'type'    => 'store',
                'name'    => $model->name,
                'phone'   => $model->phone ?? null,
                'email'   => $model->email ?? null,
                'logo'    => $model->logo ?? null,
                'address' => $model->address ?? null,
            ];
        }

        if ($model instanceof \App\Models\VendorEmployee) {

            return [
                'id'      => $model->id,
                'type'    => 'staff',
                'name'    => trim(($model->f_name ?? '') . ' ' . ($model->l_name ?? '')),
                'phone'   => $model->phone ?? null,
                'email'   => $model->email ?? null,
                'logo'    => $model->image ?? null,   // if you store staff photo here
                'address' => null,
            ];
        }

        return null;
    }
}
