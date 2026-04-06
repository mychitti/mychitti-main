<?php

namespace App\Models;

use App\Scopes\ZoneScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Carbon;

/** 
 * Class Banner
 *
 * @property int $id
 * @property string $title
 * @property string $type
 * @property string|null $image
 * @property bool $status
 * @property string $data
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property int $zone_id
 * @property int $module_id
 * @property bool $featured
 * @property string|null $default_link
 * @property string $created_by
 */
class Banner extends Model
{
    use HasFactory;
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'title',
        'type',
        'image',
        'status',
        'data',
        'zone_id',
        'module_id',
        'featured',
        'default_link',
        'created_by',
        'platform',
        'sort_order',
        'publish_at',
    ];

    /**
     * @var string[]
     */
    protected $casts = [
        'data' => 'integer',
        'status' => 'boolean',
        'zone_id' => 'integer',
        'module_id' => 'integer',
        'featured' => 'boolean',
        'sort_order' => 'integer',
        'publish_at' => 'datetime',
    ];

    /**
     * @return MorphMany
     */
    public function translations(): MorphMany
    {
        return $this->morphMany(Translation::class, 'translationable');
    }

    /**
     * @param $value
     * @return mixed
     */
    public function getTitleAttribute($value): mixed
    {
        if (count($this->translations) > 0) {
            foreach ($this->translations as $translation) {
                if ($translation['key'] == 'title') {
                    return $translation['value'];
                }
            }
        }

        return $value;
    }

    /**
     * @return BelongsTo
     */
    public function zone(): BelongsTo
    {
        return $this->belongsTo(Zone::class);
    }

    /**
     * @return BelongsTo
     */
    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }

    /**
     * @param $query
     * @param $module_id
     * @return mixed
     */
    public function scopeModule($query, $module_id): mixed
    {
        return $query->where('module_id', $module_id);
    }

    /**
     * @param $query
     * @return mixed
     */
    public function scopeActive($query): mixed
    {
        return $query->where('status', '=', 1)
            ->where(function ($q) {
                $q->whereNull('publish_at')->orWhere('publish_at', '<=', now());
            });
    }

    /**
     * @param $query
     * @return mixed
     */
    public function scopeFeatured($query): mixed
    {
        return $query->where('featured', '=', 1);
    }

    public function scopePlatform($query, $platform): mixed
    {
        return $query->whereIn('platform', [$platform, 'all']);
    }

    /**
     * @return void
     */
    protected static function booted(): void
    {
        static::addGlobalScope(new ZoneScope);

        static::addGlobalScope('translate', function (Builder $builder) {
            $builder->with(['translations' => function ($query) {
                return $query->where('locale', app()->getLocale());
            }]);
        });
    }
}
