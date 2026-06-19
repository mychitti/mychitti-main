<?php

namespace App\Http\Controllers\Vendor;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Models\EmployeeTimeCard;
use App\Models\ShiftSwap;
use App\Models\StoreShift;
use App\Models\VendorEmployee;
use Brian2694\Toastr\Facades\Toastr;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ShiftController extends Controller
{
    public function index()
    {
        $shifts = StoreShift::where('store_id', Helpers::get_store_id())
            ->with(['employees' => fn($q) => $q->where('status', 1)->orderBy('f_name')])
            ->latest()->paginate(10);
        return view('vendor-views.shift.index', compact('shifts'));
    }

    public function store(Request $request)
    {

        $request->validate([
            'name' => 'required|string',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i',
        ]);

        StoreShift::create([
            'store_id'    => Helpers::get_store_id(),
            'name'        => $request->name,
            'start_time'  => $request->start_time,
            'end_time'    => $request->end_time,
        ]);


        Toastr::success('Shift created successfully.');
        return back();
    }

    public function edit($id)
    {
        $shift = StoreShift::findOrFail($id);
        return view('store_shifts.edit', compact('shift'));
    }

    public function update(Request $request)
    {
        $id = $request->shift_id;
        $request->validate([
            'name' => 'required|string',
            'start_time' => 'required',
            'end_time' => 'required',
        ]);

        $shift = StoreShift::findOrFail($id);
        $shift->update($request->all());
        Toastr::success('Shift updated successfully.');
        return redirect()->back();
    }

    public function delete($id)
    {
        StoreShift::findOrFail($id)->delete();
        Toastr::success('Shift deleted successfully.');

        return redirect()->route('vendor.shifts.index');
    }

    // Window (Carbon start/end) for a shift on a given date; handles overnight shifts.
    private function shiftWindow($shift, Carbon $onDate): array
    {
        $start = $onDate->copy()->setTimeFromTimeString(substr($shift->start_time ?: '00:00:00', 0, 8));
        $end   = $onDate->copy()->setTimeFromTimeString(substr($shift->end_time ?: '00:00:00', 0, 8));
        if ($end->lessThanOrEqualTo($start)) {
            $end->addDay();
        }
        return [$start, $end];
    }

    public function liveWork()
    {
        ShiftSwap::ensureTable();
        $storeId = Helpers::get_store_id();
        $now     = Carbon::now();
        $today   = $now->toDateString();

        $shifts = StoreShift::where('store_id', $storeId)
            ->with(['employees' => fn($q) => $q->where('status', 1)])
            ->get();

        // Currently clocked-in (live) workers — open time card today.
        $liveCards = EmployeeTimeCard::where('vendor_id', $storeId)
            ->whereDate('date', $today)
            ->whereNotNull('in_time')
            ->whereNull('out_time')
            ->get();
        $liveEmpIds = $liveCards->pluck('emp_id')->all();
        $liveEmployees = VendorEmployee::whereIn('id', $liveEmpIds)->with('storeShift')->get()
            ->map(function ($e) use ($liveCards) {
                $card = $liveCards->firstWhere('emp_id', $e->id);
                return ['employee' => $e, 'in_time' => $card ? Carbon::parse($card->in_time) : null];
            });

        // Determine present + next shift by clock time.
        $present = null; $next = null; $nextStart = null;
        foreach ($shifts as $s) {
            [$start, $end] = $this->shiftWindow($s, $now->copy());
            if ($now->betweenIncluded($start, $end)) {
                $present = $s;
            }
            // earliest upcoming start today (or its window tomorrow if already past)
            $candidate = $start->greaterThan($now) ? $start : $start->copy()->addDay();
            if (!$nextStart || $candidate->lessThan($nextStart)) {
                $nextStart = $candidate; $next = $s;
            }
        }

        // Approved swaps effective today (whose duty is covered by whom).
        $todaySwaps = ShiftSwap::where('store_id', $storeId)
            ->whereDate('swap_date', $today)
            ->where('status', 'approved')
            ->with(['fromEmployee', 'toEmployee', 'shift'])
            ->get();

        // Swap requests (recent / upcoming) for the management list.
        $swaps = ShiftSwap::where('store_id', $storeId)
            ->whereDate('swap_date', '>=', $today)
            ->with(['fromEmployee', 'toEmployee', 'shift'])
            ->orderBy('swap_date')
            ->orderByDesc('id')
            ->get();

        $staff = VendorEmployee::where('store_id', $storeId)->where('status', 1)
            ->with('storeShift')->orderBy('f_name')->get();

        // Active staff with NO shift assigned yet — surfaced so they're not invisible in the overview.
        $unassignedStaff = $staff->filter(fn($e) => empty($e->store_shift_id))->values();

        // Effective roster for a shift today = base assigned staff, minus those whose duty was
        // swapped out (approved), plus the people covering for them.
        $buildRoster = function ($shift) use ($todaySwaps) {
            if (!$shift) return collect();
            $sw = $todaySwaps->where('store_shift_id', $shift->id);
            $coveredOut = $sw->pluck('from_emp_id')->all();
            $rows = collect();
            foreach ($shift->employees as $e) {
                $rows->push(['emp' => $e, 'cover_for' => null, 'covered_out' => in_array($e->id, $coveredOut)]);
            }
            foreach ($sw as $s) {
                if ($s->toEmployee) {
                    $rows->push(['emp' => $s->toEmployee, 'cover_for' => $s->fromEmployee, 'covered_out' => false]);
                }
            }
            return $rows;
        };
        $presentStaff = $buildRoster($present);
        $nextStaff    = $buildRoster($next);

        return view('vendor-views.shift.live', compact(
            'shifts', 'present', 'next', 'liveEmployees', 'liveEmpIds', 'todaySwaps', 'swaps', 'staff',
            'presentStaff', 'nextStaff', 'unassignedStaff'
        ))->with('now', $now);
    }

    public function swapStore(Request $request)
    {
        ShiftSwap::ensureTable();
        $request->validate([
            'swap_date'     => 'required|date',
            'to_emp_id'     => 'required|integer',
            'from_emp_id'   => 'nullable|integer',
            'store_shift_id'=> 'nullable|integer',
            'reason'        => 'nullable|string|max:500',
        ]);

        $storeId = Helpers::get_store_id();
        $isOwner = auth('vendor')->check();

        // A staff member can only swap out their OWN shift; the owner can swap anyone's.
        $fromId = $isOwner ? $request->from_emp_id : auth('vendor_employee')->id();
        if (!$fromId || $fromId == $request->to_emp_id) {
            Toastr::error('Pick a different staff member to cover the shift.');
            return back();
        }

        $fromEmp = VendorEmployee::where('store_id', $storeId)->find($fromId);

        $swap = ShiftSwap::create([
            'store_id'       => $storeId,
            'swap_date'      => $request->swap_date,
            'store_shift_id' => $request->store_shift_id ?: ($fromEmp->store_shift_id ?? null),
            'from_emp_id'    => $fromId,
            'to_emp_id'      => $request->to_emp_id,
            'reason'         => $request->reason,
            // Owner-created assignments are approved immediately; staff requests stay pending.
            'status'         => $isOwner ? 'approved' : 'pending',
        ]);

        if ($swap->status === 'approved') {
            $this->notifySwapApproved($swap);
        }

        Toastr::success('Shift change ' . ($isOwner ? 'assigned' : 'requested') . '.');
        return back();
    }

    public function swapStatus($id, $status)
    {
        ShiftSwap::ensureTable();
        if (!in_array($status, ['approved', 'rejected'], true)) {
            return back();
        }
        $swap = ShiftSwap::where('store_id', Helpers::get_store_id())->findOrFail($id);
        $swap->update(['status' => $status]);

        if ($status === 'approved') {
            $this->notifySwapApproved($swap);
        }

        Toastr::success('Shift change ' . $status . '.');
        return back();
    }

    // Notify the covering staff member that they've been assigned the shift.
    private function notifySwapApproved(ShiftSwap $swap): void
    {
        try {
            $swap->loadMissing(['fromEmployee', 'shift']);
            $fromName = trim(($swap->fromEmployee->f_name ?? '') . ' ' . ($swap->fromEmployee->l_name ?? '')) ?: 'a colleague';
            $shiftName = $swap->shift->name ?? 'shift';
            $date = $swap->swap_date ? Carbon::parse($swap->swap_date)->format('d M Y') : '';
            _inAppNotification(
                'Shift Assigned',
                "You've been assigned to cover {$fromName}'s {$shiftName} on {$date}.",
                null,
                $swap->to_emp_id,
                null,
                'vendor_employee'
            );
        } catch (\Throwable $e) {
            // notification failure must not block the swap
        }
    }
}
