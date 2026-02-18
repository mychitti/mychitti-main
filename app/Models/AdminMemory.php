<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminMemory extends Model
{
    protected $fillable = [
        'admin_id',
        'vendor_id',
        'user_id',
        'key',
        'value',
    ];

    protected $casts = [
        'admin_id'  => 'integer',
        'vendor_id' => 'integer',
        'user_id'   => 'integer',
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
