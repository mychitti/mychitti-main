<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ShiftSwap extends Model
{
    protected $table = 'shift_swaps';

    protected $fillable = [
        'store_id', 'swap_date', 'store_shift_id', 'from_emp_id', 'to_emp_id', 'reason', 'status',
    ];

    protected $casts = ['swap_date' => 'date'];

    public function shift()
    {
        return $this->belongsTo(StoreShift::class, 'store_shift_id');
    }

    public function fromEmployee()
    {
        return $this->belongsTo(VendorEmployee::class, 'from_emp_id');
    }

    public function toEmployee()
    {
        return $this->belongsTo(VendorEmployee::class, 'to_emp_id');
    }

    // Guarded table create — no migration files.
    public static function ensureTable(): void
    {
        if (!Schema::hasTable('shift_swaps')) {
            DB::statement("CREATE TABLE IF NOT EXISTS `shift_swaps` (
                `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                `store_id` BIGINT UNSIGNED NOT NULL,
                `swap_date` DATE NOT NULL,
                `store_shift_id` BIGINT UNSIGNED NULL,
                `from_emp_id` BIGINT UNSIGNED NOT NULL,
                `to_emp_id` BIGINT UNSIGNED NOT NULL,
                `reason` VARCHAR(500) NULL,
                `status` VARCHAR(20) NOT NULL DEFAULT 'pending',
                `created_at` TIMESTAMP NULL,
                `updated_at` TIMESTAMP NULL,
                PRIMARY KEY (`id`),
                KEY `ss_store_date_idx` (`store_id`, `swap_date`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }
    }
}
