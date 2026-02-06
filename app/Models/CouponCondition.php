<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

 
class CouponCondition extends Model
{
    use HasFactory;
public function coupon()
{
    return $this->hasOne(Coupon::class, 'coupon_condition_id', 'id');
}

}
