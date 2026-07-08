<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** @mixin \Eloquent */
class BusinessSetting extends Model
{
    protected $fillable = ['key'];

    // Bust the cached copy (Helpers::get_settings / get_business_settings) whenever a
    // setting is written or removed via Eloquent, so edits apply on the next request.
    protected static function booted(): void
    {
        $forget = function (self $model) {
            \App\CentralLogics\Helpers::forget_setting_cache($model->key);
        };
        static::saved($forget);
        static::deleted($forget);
    }

    public function translations()
    {
        return $this->morphMany(Translation::class, 'translationable');
    }

}
