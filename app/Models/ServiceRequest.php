<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceRequest extends Model
{
    use HasFactory;
    protected $fillable = [
        'notified'
    ];
    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id', 'id')->withoutGlobalScopes();
    }

    public function accepted()
    {
        return $this->hasOne(AcceptedServiceRequest::class, 'service_request_id');
    }
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    public function gatePass()
    {
        return $this->hasOne(GatePass::class, 'service_id', 'id');
    }
    public function quotation()
    {
        return $this->hasOne(InServiceQuotation::class, 'service_id');
    }
}
