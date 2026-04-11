<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WebsiteError extends Model
{
    protected $fillable = [
        'class',
        'message',
        'file',
        'line',
        'trace',
        'url',
        'method',
        'referer',
        'admin_id',
        'staff_id',
        'user_id',
        'store_id',
        'ip',
        'user_agent',
        'status',
        'request_data',
    ];

    const STATUSES = ['open', 'resolved', 'reopen', 'closed'];

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }
}
