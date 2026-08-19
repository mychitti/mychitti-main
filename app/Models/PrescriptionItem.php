<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PrescriptionItem extends Model
{
    protected $fillable = [
        'prescription_id',
        'medicine_name',
        'inventory_item_id',
        // Dosage form the line is written as — TAB., CAP., SYR. — kept apart from the name so the
        // printed sheet can column it, and free notes kept apart from the food timing so "After
        // food" stays a pickable value rather than being buried in a sentence.
        'type',
        'notes',
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
