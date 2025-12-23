<?php 

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    protected $fillable = ['name', 'address', 'store_id'];

    public function inventoryItems()
    {
        return $this->belongsToMany(InventoryItem::class, 'branch_inventory_item')
                    ->withPivot('price');
    }

  
public function services()
{
    return $this->belongsToMany(Item::class, 'branch_item', 'branch_id', 'item_id')
                ->withPivot('price');
}
}
