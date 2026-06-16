<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeeConcession extends Model
{
    protected $table = 'fee_concessions';

    protected $fillable = [
        'store_id', 'branch_id', 'name', 'type', 'value',
        'max_amount', 'description', 'is_active', 'sort_order',
    ];

    protected $casts = [
        'value'      => 'float',
        'max_amount' => 'float',
        'is_active'  => 'boolean',
        'sort_order' => 'integer',
    ];

    public function getLabelAttribute(): string
    {
        if ($this->type === 'percent') {
            $v = rtrim(rtrim(number_format($this->value, 2), '0'), '.');
            return $v . '%' . ($this->max_amount ? ' (cap ' . \App\CentralLogics\Helpers::format_currency($this->max_amount) . ')' : '');
        }
        return \App\CentralLogics\Helpers::format_currency($this->value);
    }
}
