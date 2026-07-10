<?php

namespace App\Models;

use App\Scopes\ZoneScope;
use App\Scopes\StoreScope;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Item extends Model
{
    use HasFactory;
    use SoftDeletes;
    protected $guarded = ['id'];
    protected $casts = [
        'tax' => 'float',
        'price' => 'float',
        'status' => 'integer',
        'discount' => 'float',
        'avg_rating' => 'float',
        'set_menu' => 'integer',
        'category_id' => 'integer',
        'store_id' => 'integer',
        'reviews_count' => 'integer',
        'recommended' => 'integer',
        'maximum_cart_quantity' => 'integer',
        'organic' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'veg' => 'integer',
        'images' => 'array',
        'module_id' => 'integer',
        'is_approved' => 'integer',
        'stock' => 'integer',
        "min_price" => 'float',
        "max_price" => 'float',
        'order_count' => 'integer',
        'rating_count' => 'integer',
        'unit_id' => 'integer'
    ];
    protected $fillable = [
        'name',
        'slug',
    ];

    protected $appends = ['unit_type'];
    public function branches()
    {
        return $this->belongsToMany(Branch::class, 'branch_item', 'item_id', 'branch_id')
            ->withPivot('price');
    }

    public function scopeRecommended($query)
    {
        return $query->where('recommended', 1);
    }

    public function carts()
    {
        return $this->morphMany(Cart::class, 'item');
    }

    public function temp_product()
    {
        return $this->hasOne(TempProduct::class, 'item_id')->with('translations');
    }

    public function scopeDiscounted($query)
    {
        return $query->where('discount', '>', 0);
    }

    public function translations()
    {
        return $this->morphMany(Translation::class, 'translationable');
    }

    public function scopeModule($query, $module_id)
    {
        return $query->where('module_id', $module_id);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 1)->where('is_approved', 1)->whereHas('store', function ($query) {
            return $query->where('status', 1);
        });
    }

    public function scopePopular($query)
    {
        return $query->orderBy('order_count', 'desc');
    }
    public function scopeApproved($query)
    {
        return $query->where('is_approved', 1);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class)->latest();
    }

    public function whislists()
    {
        return $this->hasMany(Wishlist::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }

    public function module()
    {
        return $this->belongsTo(Module::class, 'module_id');
    }

    // public function scopeHasRunningFlashSale($query)
    // {
    //     return $query->whereHas('flashSaleItems', function ($query) {
    //         $query->whereHas('flashSale', function ($query) {
    //             $query->Running();
    //         });
    //     });
    // }

    public function flashSaleItems()
    {
        return $this->hasMany(FlashSaleItem::class);
    }

    public function getUnitTypeAttribute()
    {
        return $this->unit ? $this->unit->unit : null;
    }

    public function getNameAttribute($value)
    {
        if (count($this->translations) > 0) {
            foreach ($this->translations as $translation) {
                if ($translation['key'] == 'name') {
                    return $translation['value'];
                }
            }
        }

        return $value;
    }

    public function getDescriptionAttribute($value)
    {
        if (count($this->translations) > 0) {
            foreach ($this->translations as $translation) {
                if ($translation['key'] == 'description') {
                    return $translation['value'];
                }
            }
        }

        return $value;
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }
    // Stores that carry/offer this item — the item_store pivot replaces the legacy
    // comma-separated items.store_ids column.
    public function stores()
    {
        return $this->belongsToMany(Store::class, 'item_store', 'item_id', 'store_id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function pharmacy_item_details()
    {
        return $this->hasOne(PharmacyItemDetails::class, 'item_id');
    }

    public function orders()
    {
        return $this->hasMany(OrderDetail::class);
    }

    protected static function booted()
    {
        if (auth('vendor')->check() || auth('vendor_employee')->check()) {
            static::addGlobalScope(new StoreScope);
        }

        static::addGlobalScope(new ZoneScope);

        // static::addGlobalScope('translate', function (Builder $builder) {
        //     $builder->with(['translations' => function ($query) {
        //         return $query->where('locale', app()->getLocale());
        //     }]);
        // });
    }


    public function scopeType($query, $type)
    {
        if ($type == 'veg') {
            return $query->where('veg', true);
        } else if ($type == 'non_veg') {
            return $query->where('veg', false);
        }
        return $query;
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class);
    }

    protected static function boot()
    {
        parent::boot();

        // Auto-generate slug on create if empty
        static::creating(function ($model) {
            if (empty($model->slug) && !empty($model->name)) {
                $model->slug = $model->generateSlug($model->name);
            }
        });

        // Regenerate slug on name change (optional — remove if you don't want slugs to change)
        static::updating(function ($model) {
            if ($model->isDirty('name')) {
                $model->slug = $model->generateSlug($model->name);
            }
        });
    }


    private function generateSlug(string $name): string
    {
        $slug = Str::slug($name);

        // Get all slugs starting with the base slug
        $allSlugs = static::withoutGlobalScopes()->where('slug', 'like', $slug . '%')->pluck('slug')->toArray();

        // If base slug not used, return it
        if (! in_array($slug, $allSlugs, true)) {
            return $slug;
        }

        // Find the highest numeric suffix
        $max = 0;
        foreach ($allSlugs as $s) {
            if (preg_match('/^' . preg_quote($slug, '/') . '-([0-9]+)$/', $s, $m)) {
                $n = (int) $m[1];
                if ($n > $max) $max = $n;
            }
        }

        return $slug . '-' . ($max + 1);
    }

    /**
     * Optional: increment an existing slug string "slug-N" -> "slug-(N+1)" or "slug" -> "slug-1"
     */
    private function incrementSlug(string $slug): string
    {
        if (preg_match('/-(\d+)$/', $slug, $m)) {
            return preg_replace('/-(\d+)$/', '-' . ($m[1] + 1), $slug);
        }
        return $slug . '-1';
    }

    /**
     * Optional: create with retries on duplicate-key (useful under high concurrency)
     */
    public static function createUnique(array $attributes)
    {
        $maxTries = 6;
        $tries = 0;

        if (empty($attributes['slug']) && !empty($attributes['name'])) {
            $attributes['slug'] = (new static)->generateSlug($attributes['name']);
        }

        while ($tries < $maxTries) {
            try {
                return static::create($attributes);
            } catch (\Illuminate\Database\QueryException $e) {
                // MySQL duplicate key error code is 1062
                if (isset($e->errorInfo[1]) && $e->errorInfo[1] == 1062) {
                    $attributes['slug'] = (new static)->incrementSlug($attributes['slug']);
                    $tries++;
                    continue;
                }
                throw $e;
            }
        }

        throw new \Exception("Could not generate unique slug after {$maxTries} attempts.");
    }
}
