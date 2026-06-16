<?php

namespace App\Models;

use App\Modules\School\Concerns\BranchScoped;
use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    use BranchScoped;

    protected $table = 'school_students';

    protected $fillable = [
        'store_id', 'branch_id', 'admission_no', 'admission_date',
        'first_name', 'last_name', 'name', 'dob', 'gender', 'blood_group', 'category', 'photo',
        'guardian_name', 'guardian_phone', 'guardian_relation',
        'phone', 'email', 'address', 'city', 'state', 'pincode', 'status',
    ];

    protected $casts = [
        'dob'            => 'date',
        'admission_date' => 'date',
    ];

    public function enrollments()
    {
        return $this->hasMany(StudentEnrollment::class, 'student_id');
    }

    public function currentEnrollment()
    {
        return $this->hasOne(StudentEnrollment::class, 'student_id')->where('status', 1)->latestOfMany();
    }

    /** Generate the next unique admission number for a store (format from StoreConfig). */
    public static function generateAdmissionNo(int $storeId): string
    {
        $config    = StoreConfig::where('store_id', $storeId)->first();
        $prefix    = $config?->admission_no_prefix ?? 'ADM';
        $padding   = (int) ($config?->admission_no_padding ?? 4);
        $minSerial = (int) ($config?->admission_no_serial ?? 1);

        // Sequence runs store-wide or per-branch per the store's serial-scope setting.
        $last = school_serial_base(static::class, $storeId)->orderByDesc('id')->value('admission_no');
        $next = 1;
        if ($last && preg_match('/(\d+)$/', $last, $m)) {
            $next = (int) $m[1] + 1;
        }
        $next = max($next, $minSerial);

        do {
            $candidate = $prefix . str_pad($next, $padding, '0', STR_PAD_LEFT);
            if (!school_serial_base(static::class, $storeId)->where('admission_no', $candidate)->exists()) {
                return $candidate;
            }
            $next++;
        } while (true);
    }
}
