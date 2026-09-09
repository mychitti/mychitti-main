<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * A curated brand in the shared brand pool.
 *
 * Admin maintains the list; vendors pick from it when adding inventory items;
 * SEO landing pages render "Brand + Service" chips (e.g. "LG AC Repair").
 */
class BrandPool extends Model
{
    protected $table = 'brand_pool';

    protected $fillable = [
        'name', 'slug', 'status', 'usage_count',
    ];

    protected $casts = [
        'usage_count' => 'integer',
    ];

    // ── Relationships ───────────────────────────────────────────────────

    /** Service categories this brand is associated with. */
    public function categories()
    {
        return $this->belongsToMany(Category::class, 'brand_pool_category', 'brand_pool_id', 'category_id');
    }

    /** Specific services (items with module_id = 6, e.g. AC Repair) this brand is associated with. */
    public function services()
    {
        return $this->belongsToMany(Item::class, 'brand_pool_service', 'brand_pool_id', 'item_id')->withoutGlobalScopes();
    }

    /** Inventory items that picked this brand. */
    public function inventoryItems()
    {
        return $this->hasMany(InventoryItem::class, 'brand_pool_id');
    }

    // ── Scopes ──────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    // ── Helpers ─────────────────────────────────────────────────────────

    /**
     * Generate a unique slug from the brand name.
     */
    public static function makeSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $i = 1;

        while (
            static::where('slug', $slug)
                ->when($ignoreId, fn($q) => $q->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = $base . '-' . (++$i);
        }

        return $slug;
    }

    /**
     * Recount how many inventory items reference this brand and persist.
     */
    public function refreshUsageCount(): void
    {
        $this->usage_count = InventoryItem::where('brand_pool_id', $this->id)->count();
        $this->save();
    }
}
