<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScheduledEmail extends Model
{
    protected $fillable = [
        'subject',
        'message',
        'send_to',
        'scheduled_at',
        'sent_at',
        'status',
        'error',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'sent_at'      => 'datetime',
    ];

    public function scopePending($query)
    {
        return $query->where('status', 'pending')
                     ->where('scheduled_at', '<=', now('Asia/Kolkata'));
    }
}
