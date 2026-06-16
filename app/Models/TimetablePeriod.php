<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TimetablePeriod extends Model
{
    protected $table = 'timetable_periods';

    protected $fillable = [
        'store_id', 'branch_id', 'period_no', 'name',
        'start_time', 'end_time', 'is_break', 'sort_order', 'status',
    ];

    protected $casts = [
        'is_break' => 'boolean',
    ];

    public function label(): string
    {
        $t = $this->name;
        if ($this->start_time && $this->end_time) {
            $t .= ' (' . \Carbon\Carbon::parse($this->start_time)->format('g:i A') . '–' . \Carbon\Carbon::parse($this->end_time)->format('g:i A') . ')';
        }
        return $t;
    }
}
