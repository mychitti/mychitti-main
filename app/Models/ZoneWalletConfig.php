<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ZoneWalletConfig extends Model
{
    protected $fillable = ['zone_id', 'category_id', 'min_balance'];

    public function zone()
    {
        return $this->belongsTo(Zone::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
 