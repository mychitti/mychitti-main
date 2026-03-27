<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Brian2694\Toastr\Facades\Toastr;
use App\CentralLogics\Helpers;
use App\Exports\MasterLedgerExport;
use App\Imports\MasterLedgerImport;
use App\Models\StoreAccount;
use App\Models\StoreCustomer;
use App\Models\StoreLedgerEntry;
use App\Models\StoreVoucher;
use Illuminate\Support\Facades\View;
use Maatwebsite\Excel\Facades\Excel;

class MasterLedgerController extends Controller
{
    public function entry(Request $request)
    {
        $storeId = 0;
        $user = auth('admin')->user();

        $user_type = 'admin';

        // Get account info
        if (_storeAccountType() == 'ledger') {
            $account = StoreAccount::where('id', $request->category)->firstOrFail();
            $method = $account->acc_type; // debit / credit
            $account_id = $request->category;
        } else { // normal account type
            $account = Helpers::ensureAdminDefaultExpensesAccount();
            $method = 'debit'; // debit / credit
            $account_id = $account->id;
        }

        // customer account info
        $customer  = StoreCustomer::where('id', $request->customer_id)->first();
        $customerAccount = Helpers::ensureCustomerLedger($customer);
        $customerMethod = $method == 'credit' ? 'debit' : 'credit'; // debit / credit

        // Handle voucher
        if ($request->voucher_number) {
            $voucher = StoreVoucher::find($request->voucher_number);
        } else {
            $voucherNo = Helpers::_generateVoucherNumber($storeId);
            try {
                $voucher = StoreVoucher::create([
                    'store_id'        => $storeId,
                    'voucher_number'  => $voucherNo,
                    'voucher_type'    => 'Payment',
                    'voucher_date'    => $request->date ?? now(),
                    'total_amount'    => $request->amount,
                    'narration'       => $request->description,
                    'request_no'      => null,
                    'credit_entity_type' => $customerMethod == 'credit' ? 'customer' : 'admin',
                    'debit_entity_type' => $customerMethod == 'debit' ? 'customer' : 'admin',
                    'status'          => $request->status ?? 'pending',
                    'completed_at'    => $request->status == 'approved' ? (($request->date) ?? now()) : null,
                    'created_by'      => $user->id,
                    'created_by_type' => $user_type,
                    'created_at'      => now(),
                    'updated_at'      => now(),
                ]);
            } catch (\Exception $e) {
                dd($e->getMessage());
            }
        }
        // Check file upload
        $file = null;
        if ($request->hasFile('file')) {
            $extension = $request->file('file')->getClientOriginalExtension();
            $file = Helpers::upload('store/docs/', $extension, $request->file('file'));
        }

        // Prepare entries for double-entry
        $entries = [
            // Original account entry
            [
                'store_id'          => $storeId,
                'voucher_type'      => $voucher->voucher_type,
                'voucher_id'        => $voucher->id,
                'entry_date'        => $request->date ?? $voucher->voucher_date,
                'account_id'        => $account_id,
                'debit'             => $method == 'debit' ? $request->amount : 0.00,
                'credit'            => $method == 'credit' ? $request->amount : 0.00,
                'narration'         => $request->description,
                'request_no'        => $voucher->request_no,
                'status'            => $request->status ?? 'pending',
                'completed_at'      => $request->status == 'approved' ? (($request->date ?? $voucher->voucher_date) ?? now()) : null,
                'gst_amount'        => $request->gst_amount,
                'payment_mode'      => $request->payment_mode,
                'note'              => $request->note,
                'document'          => $file,
                'created_by'        => $user->id,
                'created_by_type'   => $user_type,
                'created_at'        => now(),
                'updated_at'        => now(),
            ],
            // Opposite customer entry
            [
                'store_id'          => $storeId,
                'voucher_type'      => $voucher->voucher_type,
                'voucher_id'        => $voucher->id,
                'entry_date'        => $request->date ?? $voucher->voucher_date,
                'account_id'        => $customerAccount->id ?? null, // customer account
                'debit'             => $customerMethod == 'debit' ? $request->amount : 0.00, // opposite
                'credit'            => $customerMethod == 'credit' ? $request->amount : 0.00,  // opposite
                'narration'         => $request->description,
                'request_no'        => $voucher->request_no,
                'status'            => $request->status ?? 'pending',
                'gst_amount'        => $request->gst_amount,
                'payment_mode'      => $request->payment_mode,
                'note'              => $request->note,
                'document'          => $file,
                'completed_at'      => $request->status == 'approved' ? (($request->date ?? $voucher->voucher_date) ?? now()) : null,
                'created_by'        => $user->id,
                'created_by_type'   => $user_type,
                'created_at'        => now(),
                'updated_at'        => now(),
            ],
        ];
        if ($request->status == 'approved') {
            _saveDayBookEntry($request->amount, $method, $storeId, $voucher->narration, null, $voucher?->id);
        }

        // Save ledger entries
        foreach ($entries as $entry) {
            $existingEntry = StoreLedgerEntry::where('voucher_id', $voucher->id)
                ->where('account_id', $entry['account_id'])
                ->where('store_id', $storeId)
                ->where('debit', $entry['debit'])
                ->where('credit', $entry['credit']) 
                ->first();

            if (!$existingEntry) {
                StoreLedgerEntry::create($entry);
            } else {
                Toastr::warning('Ledger entry for this voucher and account already exists');
            }
        }
        _auditLogs('Created Master Ledger entry (Voucher : ' . $voucher->voucher_number . ')');

        Toastr::success('Entries Saved Successfully');
        return back();
    }

    public function export(Request $request)
    {
        $storeId = 0;

        $query = StoreLedgerEntry::with([
            'voucher',
            'account',
        ])
            ->where('store_id', $storeId)
            ->whereHas('voucher', function ($q) {
                $q->where(function ($q2) {
                    $q2->where('debit_entity_type', 'store')
                        ->orWhere('credit_entity_type', 'store');
                });
            })
            ->where(function ($q) {
                // If store is credit entity → credit > 0
                $q->whereHas('voucher', function ($sub) {
                    $sub->where('credit_entity_type', 'store');
                })->where('credit', '>', 0)
                    // Else if store is debit entity → debit > 0
                    ->orWhere(function ($sub) {
                        $sub->whereHas('voucher', function ($inner) {
                            $inner->where('debit_entity_type', 'store');
                        })->where('debit', '>', 0);
                    });
            });
        $entries = $query->get();

        $headings = [
            'Sl',
            'Entry Date',
            'Voucher Type',
            'Status',
            'Voucher Number',
            'Ledger Account',
            'Credit',
            'Debit',
            'GST Amount',
            'Narration',
            'Payment Mode',
            'Note',
            'Completed At',
            'Created At',
        ];
        $rows = [];
        foreach ($entries as $key => $entry) {
            $rows[] = [
                $key + 1,
                $entry->entry_date,
                $entry->voucher_type,
                $entry->status,
                $entry->voucher?->voucher_number,
                $entry->account?->name,
                $entry->credit,
                $entry->debit,
                $entry->gst_amount,
                $entry->narration,
                $entry->payment_mode,
                $entry->note,
                $entry->completed_at,
                $entry->created_at,
            ];
        }
        _auditLogs('Exported Master Ledger entries');

        return Excel::download(new MasterLedgerExport($rows, $headings), 'master_ledger.xlsx');
    }
    public function import(Request $request)
    {
        $file = $request->file('file');
        $import = new MasterLedgerImport(0);
        Excel::import($import, $file);
        if (!empty($import->failedRows)) {
            // dd($import->failedRows);
        } else {
        }

        Toastr::success('Excel file imported successfully.');
        return back();
    }
    public function get_entry_details(Request $request)
    {
        // prx($request->all());
        $entry = StoreLedgerEntry::with(['voucher', 'storeUser', 'account'])->where('id', $request->id)->first();
        $html = View::make('admin-views.account.master-ledger.entry-details', compact('entry'));
        echo $html;
    }
}
