<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
 
class StoreShift extends Model
{
    protected $table = 'store_shifts';

    protected $fillable = ['store_id','name', 'start_time', 'end_time', 'grace_minutes', 'working_days'];

    public function employees()
    {
        return $this->hasMany(VendorEmployee::class, 'store_shift_id');
    }
}
