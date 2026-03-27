<?php

namespace App\Http\Controllers\Admin;

use App\CentralLogics\Helpers;
use App\Exports\MaintananceExport;
use App\Http\Controllers\Controller;
use App\Imports\MaintananceImport;
use App\Models\AccountOption;
use App\Models\MonthlyMaintanance;
use App\Models\StoreAccount;
use App\Models\StoreConfig;
use App\Models\StoreLedgerEntry;
use App\Models\StoreVoucher;
use Brian2694\Toastr\Facades\Toastr;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class MaintananceController extends Controller
{
    public function index()
    {
        $store_id = 0;
        $search = request('search') ?? '';
        $status = request('status');

        $preset = request('date_range') ?? 'this_year';
        $custom = request('custom_date_range') ?? null;
        $range = Helpers::calculatePresetDates($preset, $custom);
        $formatted_from  = $range['start'];
        $formatted_to = $range['end'];

        // reminder start before (days)
        $storeConfig = StoreConfig::where('store_id', $store_id)->first();
        $data['day_before'] = $storeConfig && $storeConfig->reminder_day_before ? $storeConfig->reminder_day_before : 2;

        $expense_types = AccountOption::where('type', 'expense_type')->where('store_id', $store_id)->get();
        $masters = MonthlyMaintanance::with('debitAccount', 'creditAccount')
            ->where('store_id', $store_id)
            ->where('master', 1)
            ->when(request('added_by'), function ($query) {
                if (request('added_by') === 'self') {

                    $query->where(function ($q) {
                        $q->where('employee_id', 0)
                            ->orWhereNull('employee_id');
                    });
                } else {
                    $query->where('employee_id', request('added_by'));
                }
            })
            ->get();

        $masterIds = $masters->pluck('id')->toArray();

        $records = MonthlyMaintanance::where('store_id', $store_id)
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('expense_type', 'like', '%' . $search . '%')
                        ->orWhere('payment_day', 'like', '%' . $search . '%')
                        ->orWhere('title', 'like', '%' . $search . '%');
                });
            })
            ->when($status, function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->whereBetween('created_at', [$formatted_from, $formatted_to])
            ->where('master', 0)
            ->whereIn('parent', $masterIds)
            ->orderBy('due', 'desc')
            ->paginate(10);
            // prx($records)
        ;
        foreach ($masters as $master) {
            $dues = MonthlyMaintanance::where('store_id', $store_id)
                ->where('master', 0)
                ->where('parent', $master->id)
                ->where('due', 1)
                ->orderBy('for_month')
                ->get(['for_month']);

            if ($dues->count() > 0) {
                $dueMonths = $dues->map(function ($due) {
                    return Carbon::parse($due->for_month)->format('F Y');
                })->toArray();

                $master->stts = 'Due for ' . implode(', ', $dueMonths);
            } else {
                $master->stts = 'No dues';
            }
        }

        $storeAccounts = StoreAccount::where('store_id', $store_id)->get();
        $staffList = Helpers::get_staff_list();

        return view('admin-views.maintenance.index', compact('data', 'staffList', 'preset', 'records', 'masters', 'expense_types', 'storeAccounts'));
    }

    public function import(Request $request)
    {
        $file = $request->file('file');
        $import = new MaintananceImport(0);
        Excel::import($import, $file);
        if (!empty($import->failedRows)) {
            dd($import->failedRows);
        } else {
        }

        Toastr::success('Excel file imported successfully.');
        return back();
    }
    public function export(Request $request)
    {
        $store_id = 0;

        $masters = MonthlyMaintanance::where('store_id', $store_id)->where('master', 1)->get();
        foreach ($masters as $master) {
            $dues = MonthlyMaintanance::where('store_id', $store_id)
                ->where('master', 0)
                ->where('parent', $master->id)
                ->where('due', 1)
                ->orderBy('for_month')
                ->get(['for_month']);

            if ($dues->count() > 0) {
                $dueMonths = [];
                foreach ($dues as $due) {
                    $monthName = Carbon::parse($due->for_month)->format('F Y');
                    $dueMonths[] = $monthName;
                }
                $master->stts = 'Due for ' . implode(', ', $dueMonths);
            } else {
                $master->stts = 'No dues';
            }
        }

        $headings = [
            'Sl',
            'Created At',
            'Type',
            'Title',
            'Amount',
            'Payment Day',
            'Status',
            'Notes',
        ];
        $rows = [];
        foreach ($masters as $key => $master) {
            $rows[] = [
                $key + 1,
                $master->created_at,
                $master->expense_type,
                $master->title,
                $master->amount,
                $master->payment_day,
                $master->stts,
                $master->notes,

            ];
        }
        _auditLogs("Exported Monthly Maintanance");

        return Excel::download(new MaintananceExport($rows, $headings), 'monthly_maintanance.xlsx');
    }
    public function mark_paid(Request $request, $id)
    {
        $record = MonthlyMaintanance::findOrFail($id);
        $record->due = 0; // Mark as paid
        $record->status = 'clear'; // Mark as paid
        $record->paid_for = now(); // Set last paid date to now
        $record->save();

        // voucher update
        $voucher = StoreVoucher::where('maintanace_id', $record->id)->first();
        if ($voucher) {
            $voucher->status = 'approved';
            $voucher->completed_at = now();
            $voucher->total_amount = $record->amount;
            $voucher->save();

            // ledger entries update 
            $ledger_entries = StoreLedgerEntry::where('voucher_id', $voucher->id)->get();
            foreach ($ledger_entries as $key => $value) {
                $value->status =  'approved';
                $value->completed_at = now();
                $value->save();
            }
        }
        _auditLogs('Monthly Maintanance (' . $record->title . ') Marked Paid');

        _saveDayBookEntry($record->amount, 'debit', $record->store_id, $record->expense_type, null, $voucher?->id);
        Toastr::success('Maintenance record marked as paid successfully.');
        return redirect()->back();
    }
    public function create()
    {
        return view('admin-views.maintenance.create');
    }

    public function store(Request $request)
    {

        // prx($request->all());
        $request->validate([
            'expense_type' => 'required',
            'title' => 'required',
            'amount' => 'required|numeric',
        ]);

        $store_id = 0;

        // save new options 
        $expenseTypeExists = AccountOption::where('type', 'expense_type')->where('name', $request->expense_type)->where('store_id', 0)->exists();
        if (!$expenseTypeExists) {
            $account_option = new AccountOption();
            $account_option->store_id = 0;
            $account_option->name = $request->expense_type;
            $account_option->type = 'expense_type';
            $account_option->save();
        }

        $maintenance = new MonthlyMaintanance();
        $maintenance->store_id = $store_id;
        $maintenance->expense_type = $request->expense_type;
        $maintenance->title = $request->title;
        $maintenance->amount = $request->amount;
        $maintenance->notes = $request->notes;
        $maintenance->credit_account = $request->to_ledger_account;
        $maintenance->debit_account = $request->from_ledger_account;
        $maintenance->status = 'clear';
        $maintenance->employee_id =  auth('admin')->id();
        $maintenance->payment_day = $request->payment_day;
        $maintenance->duration_type = $request->duration_type;
        $maintenance->start_month = $request->start_month;
        $maintenance->master = 1;
        $maintenance->save();

        _auditLogs("Added Monthly Maintanance : " . $request->title);

        Toastr::success('Maintenance record added successfully.');
        return redirect()->back();
    }

    public function edit($id)
    {
        $record = MonthlyMaintanance::findOrFail($id);
        return view('admin-views.maintenance.edit', compact('record'));
    }

    public function update_entry_price(Request $request)
    {
        $request->validate([
            'id' => 'required',
            'amount' => 'required|numeric',
        ]);
        $record = MonthlyMaintanance::findOrFail($request->id);
        $record->amount = $request->amount;
        $record->save();

        _auditLogs("Updated Monthly Maintanance Price: " . $record->title);

        return response()->json(['success' => true, 'message' => 'Price updated successfully.']);
    }
    public function status($id, $status)
    {
        $record = MonthlyMaintanance::findOrFail($id);
        $record->due = 0;
        $record->status = $status;
        $record->save();

        // DELETE VOUCHER AND LEDGER ENTRIES IF CANCELLED
        if ($status == 'cancel') {
            $voucher = StoreVoucher::where('maintanace_id', $record->id)->first();
            if ($voucher) {
                // delete ledger entries
                StoreLedgerEntry::where('voucher_id', $voucher->id)->delete();
                // delete voucher
                $voucher->delete();
            }
            _auditLogs("Cancelled Monthly Maintanance (" . $record->title . ")");
        }

        _auditLogs("Updated Monthly Maintanance (" . $record->title . ") Status to " . $status);

        Toastr::success('Maintenance record status updated successfully.');
        return redirect()->back();
    }
    public function update(Request $request)
    {
        $record = MonthlyMaintanance::findOrFail($request->id);

        $request->validate([
            'expense_type' => 'required',
            'title' => 'required',
            'amount' => 'required|numeric',
        ]);

        $record->expense_type = $request->expense_type;
        $record->title = $request->title;
        $record->amount = $request->amount;
        $record->payment_day = $request->payment_day;
        $record->notes = $request->notes;
        $record->credit_account = $request->to_ledger_account;
        $record->debit_account = $request->from_ledger_account;
        $record->save();

        _auditLogs("Updated Monthly Maintanance : " . $request->title);

        Toastr::success('Maintenance record updated successfully.');
        return redirect()->back();
    }

    public function destroy($id)
    {
        MonthlyMaintanance::destroy($id);
        Toastr::success('Maintenance record deleted successfully.');
        return redirect()->back();
    }
}
