<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class BranchInventoryItem extends Pivot
{
    protected $table = 'branch_inventory_item';
    protected $fillable = [
        'store_id',
        'branch_id',
        'inventory_item_id',
        'price',
        'gst_percent',
        'qty_left',
        'qty',
    ];
}
