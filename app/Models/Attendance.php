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

    // Length (seconds) of an employee's assigned shift; falls back to a 9h day when unassigned.
    public static function shiftSecondsFor($employee): int
    {
        $shift = $employee->storeShift ?? null;
        if ($shift && $shift->start_time && $shift->end_time) {
            $s = Carbon::parse('2000-01-01 ' . substr($shift->start_time, 0, 8));
            $e = Carbon::parse('2000-01-01 ' . substr($shift->end_time, 0, 8));
            if ($e->lessThanOrEqualTo($s)) {
                $e->addDay();
            }
            return $s->diffInSeconds($e);
        }
        return 9 * 3600;
    }

    /**
     * Per-day duty history for a staff member over a month.
     * Returns rows: ['date', 'in_time', 'out_time', 'worked_label', 'extra_seconds', 'extra_label'].
     * Extra duty prefers the value snapshotted at clock-out; falls back to computed for older rows.
     */
    public static function dutyHistory($employee, $storeId, $month = null): array
    {
        if (!$employee) {
            return [];
        }
        $month = $month ?: now()->format('Y-m');

        $cards = EmployeeTimeCard::where('vendor_id', $storeId)
            ->where('emp_id', $employee->id)
            ->where('date', '>=', $month . '-01')
            ->where('date', '<=', $month . '-31')
            ->whereNotNull('in_time')
            ->orderByDesc('date')
            ->get();

        if ($cards->isEmpty()) {
            return [];
        }

        $shiftSeconds = self::shiftSecondsFor($employee);
        $byDate = [];
        foreach ($cards as $c) {
            $d = Carbon::parse($c->date)->toDateString();
            if (!isset($byDate[$d])) {
                $byDate[$d] = ['date' => $d, 'in' => null, 'out' => null, 'worked' => 0, 'extra' => 0, 'stored' => false];
            }
            $in = Carbon::parse($c->in_time);
            if (!$byDate[$d]['in'] || $in->lt($byDate[$d]['in'])) {
                $byDate[$d]['in'] = $in;
            }
            if (!empty($c->out_time)) {
                $o = Carbon::parse($c->out_time);
                if (!$byDate[$d]['out'] || $o->gt($byDate[$d]['out'])) {
                    $byDate[$d]['out'] = $o;
                }
                $byDate[$d]['worked'] += $in->diffInSeconds($o);
            }
            if (!is_null($c->overtime_seconds ?? null)) {
                $byDate[$d]['stored'] = true;
                $byDate[$d]['extra'] += (int) $c->overtime_seconds;
            }
        }

        $rows = [];
        foreach ($byDate as $d) {
            $extra = $d['stored'] ? $d['extra'] : max(0, $d['worked'] - $shiftSeconds);
            $rows[] = [
                'date'         => $d['date'],
                'in_time'      => $d['in'],
                'out_time'     => $d['out'],
                'worked_label' => $d['worked'] > 0 ? CarbonInterval::seconds($d['worked'])->cascade()->forHumans(['short' => true, 'parts' => 2]) : '—',
                'extra_seconds' => $extra,
                'extra_label'  => $extra >= 60 ? CarbonInterval::seconds($extra)->cascade()->forHumans(['short' => true, 'parts' => 2]) : null,
            ];
        }

        return $rows;
    }

    /**
     * Punch in / out + extra-duty (overtime) for a staff member on a given day.
     * Reads the staff clock-in/out punches from EmployeeTimeCard (one or more per day).
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

        // Punches come from the self clock-in/out time cards, not the Attendance presence row.
        $cards = EmployeeTimeCard::where('vendor_id', $storeId)
            ->where('emp_id', $employee->id)
            ->whereDate('date', $date)
            ->whereNotNull('in_time')
            ->get();

        if ($cards->isEmpty()) {
            return $out;
        }

        // Earliest punch-in and latest punch-out across all of the day's cards.
        $inTime  = $cards->map(fn($r) => Carbon::parse($r->in_time))->sort()->first();
        $outCards = $cards->filter(fn($r) => !empty($r->out_time));
        $outTime = $outCards->isNotEmpty() ? $outCards->map(fn($r) => Carbon::parse($r->out_time))->sort()->last() : null;

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

        // Total worked = sum of each completed punch cycle (handles multiple cards/day).
        // Extra duty prefers the value snapshotted at clock-out (shift-change-proof); falls
        // back to computing against the current shift length for older rows.
        $workedSeconds = 0;
        $extraSeconds  = 0;
        $hasStoredExtra = false;
        foreach ($cards as $c) {
            if (!empty($c->in_time) && !empty($c->out_time)) {
                $workedSeconds += Carbon::parse($c->in_time)->diffInSeconds(Carbon::parse($c->out_time));
            }
            if (!is_null($c->overtime_seconds ?? null)) {
                $hasStoredExtra = true;
                $extraSeconds += (int) $c->overtime_seconds;
            }
        }

        if ($workedSeconds > 0) {
            $out['worked_label'] = CarbonInterval::seconds($workedSeconds)->cascade()->forHumans(['short' => true, 'parts' => 2]);

            if (!$hasStoredExtra) {
                $extraSeconds = max(0, $workedSeconds - $shiftSeconds);
            }
            if ($extraSeconds >= 60) {
                $out['extra_label'] = CarbonInterval::seconds($extraSeconds)->cascade()->forHumans(['short' => true, 'parts' => 2]);
            }
        }

        return $out;
    }
}
