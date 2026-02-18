<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Summary extends Model
{
    protected $fillable = [
        'admin_id',
        'vendor_id',
        'user_id',
        'summary',
        'messages_covered',
    ];

    protected $casts = [
        'admin_id'         => 'integer',
        'vendor_id'        => 'integer',
        'user_id'          => 'integer',
        'messages_covered' => 'integer',
    ];

    public function admin()
    {
        return $this->belongsTo(Admin::class);
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
