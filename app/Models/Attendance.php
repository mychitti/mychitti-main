<?php

namespace App\Models;

use Carbon\Carbon;
use Carbon\CarbonInterval;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasFactory;
    protected $fillable =[
        'vendor_id'
    ];

    /**
     * Punch in / out + extra-duty (overtime) for a staff member on a given day.
     * Returns: ['has', 'in_time', 'out_time', 'worked_label', 'extra_label', 'shift_name'].
     * Extra duty = time worked beyond the assigned shift's length (overtime / extra shift).
     */
    public static function dutySummary($employee, $storeId, $date = null): array
    {
        $date = $date ?: now()->toDateString();
        $out  = ['has' => false, 'in_time' => null, 'out_time' => null, 'worked_label' => null, 'extra_label' => null, 'shift_name' => null];

        if (!$employee) {
            return $out;
        }

        $rows = self::where('vendor_id', $storeId)
            ->where('employee_type', 'vendor_employee')
            ->where('employee_id', $employee->id)
            ->whereDate('date', $date)
            ->whereNotNull('in_time')
            ->get();

        if ($rows->isEmpty()) {
            return $out;
        }

        // Earliest punch-in and latest punch-out across the day's records.
        $inTime  = $rows->map(fn($r) => Carbon::parse($r->in_time))->sort()->first();
        $outRows = $rows->filter(fn($r) => !empty($r->out_time));
        $outTime = $outRows->isNotEmpty() ? $outRows->map(fn($r) => Carbon::parse($r->out_time))->sort()->last() : null;

        $out['has']      = true;
        $out['in_time']  = $inTime;
        $out['out_time'] = $outTime;

        // Shift window (fall back to 10:00–19:00 when no shift assigned).
        $shift      = $employee->storeShift ?? null;
        $out['shift_name'] = $shift->name ?? null;
        $shiftStart = $shift && $shift->start_time ? substr($shift->start_time, 0, 8) : '10:00:00';
        $shiftEnd   = $shift && $shift->end_time ? substr($shift->end_time, 0, 8) : '19:00:00';

        $start = Carbon::parse($date . ' ' . $shiftStart);
        $end   = Carbon::parse($date . ' ' . $shiftEnd);
        if ($end->lessThanOrEqualTo($start)) {
            $end->addDay(); // overnight shift
        }
        $shiftSeconds = $start->diffInSeconds($end);

        if ($outTime) {
            $workedSeconds = $inTime->diffInSeconds($outTime);
            $out['worked_label'] = CarbonInterval::seconds($workedSeconds)->cascade()->forHumans(['short' => true, 'parts' => 2]);

            $extraSeconds = max(0, $workedSeconds - $shiftSeconds);
            if ($extraSeconds >= 60) {
                $out['extra_label'] = CarbonInterval::seconds($extraSeconds)->cascade()->forHumans(['short' => true, 'parts' => 2]);
            }
        }

        return $out;
    }
}
