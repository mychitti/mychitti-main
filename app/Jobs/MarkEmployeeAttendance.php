<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class MarkEmployeeAttendance implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public function __construct()
    {
        \Log::info('MarkEmployeeAttendance job constructed');
    }
    public function handle()
    {
        $today = now()->toDateString();
        $now = now();
        $isSunday = now()->isSunday();

        $employees = DB::table('vendor_employees')
            ->where('status', 1)
            ->where('id', 7)
            ->select('id', 'store_id')
            ->get()
            ->keyBy('id');

        $presentIds = DB::table('attendances')
            ->where('date', $today)
            ->pluck('employee_id')
            ->toArray();

        $absentIds = array_diff($employees->keys()->toArray(), $presentIds);

        $holidays = DB::table('holidays')
            ->where(function ($q) {
                $q->where('is_global', 1)->orWhereNotNull('vendor_id');
            })
            ->whereDate('date', $today)
            ->get();

        $overrides = DB::table('holiday_overrides')
            ->whereDate('custom_date', $today)
            ->get()
            ->keyBy(fn($h) => $h->vendor_id . '-' . $h->holiday_id);

        $vendorHolidayMap = [];

        foreach ($holidays as $h) {
            if ($h->is_global) {
                foreach ($employees as $emp) {
                    $override = $overrides->firstWhere(
                        fn($o) =>
                        $o->holiday_id == $h->id && $o->vendor_id == $emp->store_id && $o->is_deleted == 1
                    );
                    if (!$override) {
                        $vendorHolidayMap[$emp->store_id] = true;
                    }
                }
            } else {
                $vendorHolidayMap[$h->vendor_id] = true;
            }
        }

        $data = array_map(function ($id) use ($employees, $today, $now, $vendorHolidayMap, $isSunday) {
            $vendor_id = $employees[$id]->store_id;

            $label = 'A';
            if ($isSunday) {
                $label = 'Sun';
            } elseif (isset($vendorHolidayMap[$vendor_id])) {
                $label = 'HL';
            }

            return [
                'employee_id'   => $id,
                'vendor_id'     => $vendor_id,
                'employee_type' => 'vendor_employee',
                'date'          => $today,
                'label'         => $label,
                'day'           => $now->day,
                'month'         => $now->month,
                'year'          => $now->year,
                'created_at'    => $now,
                'updated_at'    => $now,
            ];
        }, $absentIds);

        if (!empty($data)) {
            DB::table('attendances')->insert($data);
        }

        echo count($data) . ' employees marked (A / HL / Sunday).';
    }
}
