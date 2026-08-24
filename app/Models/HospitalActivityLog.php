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

    protected static function causer(): array
    {
        if (auth('vendor_employee')->check()) {
            $emp = auth('vendor_employee')->user();
            return ['vendor_employee', $emp->id, trim(($emp->f_name ?? '') . ' ' . ($emp->l_name ?? ''))];
        }
        if (auth('vendor')->check()) {
            $v = auth('vendor')->user();
            return ['vendor', $v->id, trim(($v->f_name ?? '') . ' ' . ($v->l_name ?? ''))];
        }
        if (auth('api')->check()) {
            $u = auth('api')->user();
            return ['api_user', $u->id, trim(($u->f_name ?? '') . ' ' . ($u->l_name ?? ''))];
        }

        return [null, null, null];
    }

    public static function record(int $storeId, string $subjectType, ?int $subjectId, string $action, string $description, array $properties = []): void
    {
        [$causerType, $causerId, $causerName] = static::causer();

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

    /**
     * Record an entry only if this same person has not already logged the same thing recently.
     *
     * Access and edit trails are written from places that fire repeatedly — a page the doctor
     * refreshes, an autosave that PATCHes on every debounce — and one row per keystroke is not
     * an audit trail, it is noise nobody can read. Collapsing to one row per person per window
     * gives a session-level record: "Dr X had this chart open at 15:47", which is what an access
     * log is actually asked to answer.
     */
    public static function recordOnce(int $storeId, string $subjectType, ?int $subjectId, string $action, string $description, array $properties = [], int $withinMinutes = 10): void
    {
        [$causerType, $causerId] = static::causer();

        try {
            $exists = static::where('store_id', $storeId)
                ->where('subject_type', $subjectType)
                ->where('action', $action)
                ->when($subjectId === null,
                    fn($q) => $q->whereNull('subject_id'),
                    fn($q) => $q->where('subject_id', $subjectId))
                ->when($causerId === null,
                    fn($q) => $q->whereNull('causer_id'),
                    fn($q) => $q->where('causer_id', $causerId)->where('causer_type', $causerType))
                ->where('created_at', '>=', now()->subMinutes($withinMinutes))
                ->exists();
        } catch (\Throwable $e) {
            return;
        }

        if ($exists) {
            return;
        }

        static::record($storeId, $subjectType, $subjectId, $action, $description, $properties);
    }
}
