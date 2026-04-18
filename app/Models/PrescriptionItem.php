<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PrescriptionItem extends Model
{
    protected $fillable = [
        'prescription_id',
        'medicine_name',
        'inventory_item_id',
        'dosage',
        'frequency',
        'duration',
        'instructions',
        'quantity',
        'dispensed',
        'dispensed_at',
        'dispensed_qty',
    ];

    protected $casts = [
        'dispensed'    => 'boolean',
        'dispensed_at' => 'datetime',
    ];

    public function prescription()
    {
        return $this->belongsTo(Prescription::class);
    }

    public function inventoryItem()
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }
}
