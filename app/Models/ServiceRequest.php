<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceRequest extends Model
{
    use HasFactory;
    protected $fillable = [
        'notified',
        'user_notified',
        'preferred_doctor_id',
        'preferred_date',
        'preferred_slot_id',
        'preferred_time',
        'requirements',
        'service_type',
        'patient_for',
        'patient_name',
        'patient_phone',
        // Collected by the WhatsApp booking bot, which asks for them whether the appointment is
        // for the caller or for somebody else. See WhatsAppAppointmentBot::ensurePatientColumns.
        'patient_age',
        'patient_gender',
        'patient_address',
    ];
    protected function serializeDate(\DateTimeInterface $date)
    {
        return $date
            ->setTimezone(new \DateTimeZone('Asia/Kolkata'))
            ->format('Y-m-d H:i:s');
    }
    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id', 'id')->withoutGlobalScopes();
    }
    public function leadStatus() 
    {
        return $this->hasMany(LeadStatus::class, 'service_request_id');
    }
    public function accepted()
    {
        return $this->hasOne(AcceptedServiceRequest::class, 'service_request_id');
    }
    public function acceptance()
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
