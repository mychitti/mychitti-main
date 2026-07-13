<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
 
/**
 * Lead Inbox signal tracking (Phase 3 §3.3) — one row per inbound lead action a user takes on
 * a store: click-to-call, WhatsApp click, booking request, quote request, etc. The MC Vendor Hub
 * Lead Inbox reads these; also feeds the popularity signal for hybrid search over time.
 */
class LeadSignal extends Model
{
    protected $fillable = [
        'store_id', 'user_id', 'type', 'source',
        'utm_source', 'utm_medium', 'utm_campaign', 'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ]; 

    public const TYPES = ['call', 'whatsapp', 'booking', 'quote', 'direction', 'website'];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }
}
