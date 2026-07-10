<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceZoneSeo extends Model
{
    protected $table = 'service_zone_seo';

    protected $guarded = ['id'];

    protected $casts = [
        'keywords'     => 'array',
        'faqs'         => 'array',
        'generated_at' => 'datetime',
    ];

    public function zone()
    {
        return $this->belongsTo(Zone::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    // Set only for item-level combos (item_id > 0). Category-level combos (item_id = 0) have no item.
    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id');
    }

    public function isItemLevel(): bool
    {
        return (int) $this->item_id > 0;
    }
}
