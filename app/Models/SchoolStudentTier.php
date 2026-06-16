<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SchoolStudentTier extends Model
{
    protected $table = 'school_student_tiers';

    protected $fillable = [
        'tier_name', 'min_students', 'max_students',
        'max_branches', 'price_monthly', 'price_yearly',
        'is_custom', 'is_active', 'sort_order',
    ];

    protected $casts = [
        'min_students'  => 'integer',
        'max_students'  => 'integer',
        'max_branches'  => 'integer',
        'price_monthly' => 'float',
        'price_yearly'  => 'float',
        'is_custom'     => 'boolean',
        'is_active'     => 'boolean',
        'sort_order'    => 'integer',
    ];

    /** Active tier that matches a given student count. */
    public static function forStudentCount(int $count): ?self
    {
        return self::where('is_active', true)
            ->where('min_students', '<=', $count)
            ->where(function ($q) use ($count) {
                $q->whereNull('max_students')->orWhere('max_students', '>=', $count);
            })
            ->orderBy('min_students', 'desc')
            ->first();
    }

    public function getStudentRangeAttribute(): string
    {
        return is_null($this->max_students)
            ? 'Unlimited students'
            : 'Up to ' . number_format($this->max_students) . ' students';
    }
}
