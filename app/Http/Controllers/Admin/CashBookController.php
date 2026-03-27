<?php

namespace App\Http\Controllers\Admin;

use App\CentralLogics\Helpers;
use App\Exports\DayBookExport;
use App\Http\Controllers\Controller;
use App\Imports\DayBookImport;
use App\Models\CashBook;
use App\Models\DayBook;
use App\Models\StoreBankAccount;
use App\Models\StoreBankTransaction;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class CashbookController extends Controller
{
    public function index(Request $request)
    {
        $preset = request('date_range') ?? 'today';
        $custom = request('custom_date_range') ?? null;
        $range = Helpers::calculatePresetDates($preset, $custom);
        $formatted_from  = $range['start'];
        $formatted_to = $range['end'];
        $from = $range['start']->toDateString();
        $to  = $range['end']->toDateString();

        $sales = Helpers::cashbook_calendar();

        $daybook_entries = DayBook::where('store_id', 0)->whereBetween('entry_date', [$formatted_from, $formatted_to])->get();
        $bank_entries = StoreBankTransaction::where('store_id', 0)->whereBetween('value_date', [$formatted_from, $formatted_to])->get();
        return view('admin-views.account.cash-book.index', compact('sales', 'from', 'to', 'daybook_entries', 'preset', 'bank_entries'));
    }
    public function entry(Request $request)
    {
        // prx($request->all());
        $cashbook = new CashBook();
        $cashbook->store_id = 0;
        $cashbook->entry_date = $request->date;
        $cashbook->amount = $request->amount;
        $cashbook->particular = $request->particular;
        $cashbook->type = $request->type;
        if($request->ref_type == 'invoice_id'){
            $cashbook->invoice_id = $request->invoice_id;
        }else{
            $cashbook->reference_number = $request->reference_number;
        }
        $cashbook->save();
        // prx($cashbook);

        Toastr::success('Cashbook entry saved successfully.');
        return redirect()->back();
    }
    public function import(Request $request)
    {
        $file = $request->file('file');
        Excel::import(new DayBookImport(), $file);
        _auditLogs('Imported Cashbook Entries');

        Toastr::success('Excel file imported successfully.');
        return redirect()->back();
    }
    public function export(Request $request)
    {
        $dayBook = DayBook::where('store_id', 0)->whereDate('created_at', $request->query('date'))->get();
        $data = [];
        foreach ($dayBook as $key => $entry) {
            $data[$key] = [
                $entry->created_at->format('Y-m-d'),
                $entry->particular,
                $entry->type == 'credit' ? $entry->amount : '',
                $entry->type == 'debit' ? $entry->amount : '',
            ];
        }
        _auditLogs('Exported Cashbook Entries');
        return Excel::download(new DayBookExport($data, ['Date', 'Particulars', 'Credit', 'Debit']), 'cash_book.xlsx');
    }
}
