<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** @mixin \Eloquent */
class BusinessSetting extends Model
{
    protected $fillable = ['key'];

    public function translations()
    {
        return $this->morphMany(Translation::class, 'translationable');
    }

}
