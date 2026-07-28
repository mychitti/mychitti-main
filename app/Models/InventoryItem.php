<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryItem extends Model
{
    protected $casts = [
        'images' => 'array',
         'choice_options',
        'attributes',
        'description_attributes' => 'array',
    ];
    use HasFactory; 
    protected $fillable = [ 
        'store_id',
        'item_type',
        'item_name',
        'brand',
        'model_number',
        'sku_id',
        'category_id',
        'unit',
        'mrp',
        'gst_type',
        'gst_rate',
        'gst_status',
        'hsn',
        'my_fee',
        'my_fee_type',
        'product_condition',
        'product_note',
        'storage_unit_id',
        'image',
        'images',
        'module_id',
        'variations',
        'choice_options',
        'attributes',
        'stock',
        'stock_base',
        'base_unit',
        'selling_price',
        'selling_price_basis',
        'show_on_store_page',
        'description',
        'description_attributes',
        'specifications',
        'sell_loose',
        'stock_type',
    ];
 
    public function purchaseOrders()
    {
        return $this->hasMany(PurchaseOrder::class, 'inventory_item_id');
    }
    public function itemunit()
    {
        return $this->belongsTo(Unit::class, 'unit');
    }
    public function secondaryUnit()
    {
        return $this->belongsTo(Unit::class, 'secondary_unit');
    }
    public function orderDetails()
    {
        return $this->hasMany(InventoryOrderDetail::class, 'item_id');
    }
    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }
    public function subcategory()
    {
        return $this->belongsTo(Category::class, 'sub_category_id');
    }
    public function storage_unit()
    {
        return $this->belongsTo(StorageUnit::class, 'storage_unit_id');
    }
    public function entries(): HasMany
    {
        return $this->hasMany(ItemEntry::class, 'item_id');
    }
    public function branches()
    {
        return $this->belongsToMany(Branch::class, 'branch_inventory_item')
            ->withPivot('price');
    }

    protected static function booted()
    {
        // Unit rework phase 3 — keep stock_base (the item's stock in its dimension's base unit)
        // in step with every write to `stock`, wherever it comes from. Hooking the model rather
        // than the ~17 call sites means no write path can drift. Nothing reads stock_base yet;
        // it exists so the two numbers can be reconciled before anything depends on it.
        static::saving(function ($inventoryItem) {
            $needsRecompute = !$inventoryItem->exists
                || $inventoryItem->isDirty('stock')
                || $inventoryItem->isDirty('unit')
                || $inventoryItem->stock_base === null; // progressive backfill on any save
            if (!$needsRecompute) {
                return;
            }
            if (!\Illuminate\Support\Facades\Schema::hasColumn('inventory_items', 'stock_base')) {
                return;
            }
            [$base, $baseUnit] = _stockBaseFor($inventoryItem->unit, $inventoryItem->stock);
            $inventoryItem->stock_base = $base;
            $inventoryItem->base_unit  = $baseUnit;
        });

        static::saved(function ($inventoryItem) {
            if ($inventoryItem->wasChanged('stock')) {
                \App\Models\Item::withoutGlobalScopes()
                    ->where('inventory_item_id', $inventoryItem->id)
                    ->update(['stock' => $inventoryItem->stock]);
            }
        });
    }
}
