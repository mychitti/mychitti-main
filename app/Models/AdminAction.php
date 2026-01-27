<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
 
class AdminAction extends Model
{
    use HasFactory;

    protected $fillable = [
       'action_type',
        'action_payload',
        'requested_by',
        'otp',
        'status',
        'expires_at' 
    ];

    public function admin()
    {
        return $this->belongsTo(Admin::class, 'requested_by');
    }

}
