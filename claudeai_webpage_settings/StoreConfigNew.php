<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StoreConfigNew extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'store_config_news';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'store_id',
        'custom_domain',
        'personal_domain',
        'website_active',
        'domain_status',
        'webpage_name',
        'webpage_email',
        'webpage_address',
        'webpage_phones',
        'gst_number',
        'webpage_latitude',
        'webpage_longitude',
        'inventory_items_position',
        'primary_color',
        'secondary_color',
        'custom_css',
        'dns_records',
        'domain_verified_at'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'website_active' => 'boolean',
        'webpage_phones' => 'array',
        'custom_css' => 'array',
        'dns_records' => 'array',
        'domain_verified_at' => 'datetime',
        'webpage_latitude' => 'decimal:8',
        'webpage_longitude' => 'decimal:8'
    ];

    /**
     * Get the store that owns the configuration.
     */
    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    /**
     * Get the full website URL
     */
    public function getWebsiteUrlAttribute()
    {
        if ($this->personal_domain && $this->domain_status === 'active') {
            return 'https://' . $this->personal_domain;
        }
        
        return 'https://' . ($this->custom_domain ?? $this->store->slug) . '.mychitti.net';
    }

    /**
     * Check if custom domain is configured and active
     */
    public function hasCustomDomain()
    {
        return !empty($this->personal_domain) && $this->domain_status === 'active';
    }

    /**
     * Get phone numbers as array
     */
    public function getPhoneNumbersAttribute()
    {
        return $this->webpage_phones ?? [];
    }

    /**
     * Scope for active websites
     */
    public function scopeActive($query)
    {
        return $query->where('website_active', true);
    }

    /**
     * Scope for verified domains
     */
    public function scopeVerified($query)
    {
        return $query->where('domain_status', 'active');
    }
}