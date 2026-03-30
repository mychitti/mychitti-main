<?php

namespace App\Jobs;

use App\Models\VendorEmployee;
use App\Models\EmployeeTimeCard;
use App\Models\StoreShift;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue; 
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class PunchInReminder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle()
    {
        $employees = VendorEmployee::where('status', 1)->where('id', 7)->get();

        $today = Carbon::today();

        foreach ($employees as $employee) {

            $shift = StoreShift::where('id', $employee->store_shift_id)->first();

            if (!$shift) {
                continue; // skip if no shift assigned
            }

            //            $shiftStart    = Carbon::parse($shift->start_time);   // 09:00 AM
            // $shiftEnd      = Carbon::parse($shift->end_time);     // 06:00 PM

            $lateLimit     = Carbon::parse($shift->start_time)->addMinutes(30);
            $checkoutLimit = Carbon::parse($shift->end_time)->addMinutes(30);


            $timeCard = EmployeeTimeCard::where('emp_id', $employee->id)
                ->whereDate('date', $today)
                ->where('vendor_id', Helpers::get_store_id() )

                ->first();

            if (!$timeCard && now()->greaterThan($lateLimit)) {
                $this->sendReminder($employee, 'You have not clocked in yet.');
                continue;
            }
            if ($timeCard && empty($timeCard->clock_out) && now()->greaterThan($checkoutLimit)) {
                $this->sendReminder(
                    $employee,
                    'You have not clocked out yet. Please clock out.'
                );
            }
        }
    }


    private function sendReminder($employee, $message)
    {
        $url  = route('vendor.dashboard');
        _inAppNotification('Reminder', $message, null, $employee->id, $url, 'vendor_employee'); // to vendor

    }
}
