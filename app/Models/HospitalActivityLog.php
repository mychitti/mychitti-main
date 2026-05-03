<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HospitalActivityLog extends Model
{
    protected $fillable = [
        'store_id', 'subject_type', 'subject_id', 'action',
        'description', 'causer_type', 'causer_id', 'causer_name', 'properties',
    ];

    protected $casts = ['properties' => 'array'];

    public function store()
    {
        return $this->belongsTo(\App\Models\Store::class);
    }

    public static function record(int $storeId, string $subjectType, ?int $subjectId, string $action, string $description, array $properties = []): void
    {
        $causerId   = null;
        $causerType = null;
        $causerName = null;

        if (auth('vendor_employee')->check()) {
            $emp        = auth('vendor_employee')->user();
            $causerId   = $emp->id;
            $causerType = 'vendor_employee';
            $causerName = trim(($emp->f_name ?? '') . ' ' . ($emp->l_name ?? ''));
        } elseif (auth('vendor')->check()) {
            $v          = auth('vendor')->user();
            $causerId   = $v->id;
            $causerType = 'vendor';
            $causerName = trim(($v->f_name ?? '') . ' ' . ($v->l_name ?? ''));
        } elseif (auth('api')->check()) {
            $u          = auth('api')->user();
            $causerId   = $u->id;
            $causerType = 'api_user';
            $causerName = trim(($u->f_name ?? '') . ' ' . ($u->l_name ?? ''));
        }

        try {
            static::create([
                'store_id'     => $storeId,
                'subject_type' => $subjectType,
                'subject_id'   => $subjectId,
                'action'       => $action,
                'description'  => $description,
                'causer_type'  => $causerType,
                'causer_id'    => $causerId,
                'causer_name'  => $causerName,
                'properties'   => $properties ?: null,
            ]);
        } catch (\Throwable $e) {
            // Logging must never break the main flow
        }
    }
}
