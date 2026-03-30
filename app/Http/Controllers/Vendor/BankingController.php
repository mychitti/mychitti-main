<?php

namespace App\Http\Controllers\Vendor;

use App\CentralLogics\Helpers;
use App\Exports\BankTransactionExport;
use App\Http\Controllers\Controller;
use App\Imports\BankTransactionImport;
use App\Models\StoreBankAccount;
use App\Models\StoreBankTransaction;
use App\Models\StoreBankTransactionFile;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request; 
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Validation\Rule; 


class BankingController extends Controller
{
    public function bank_account(Request $request)
    {
        $year = $request->year ?? Helpers::get_financial_year();
        list($startYear, $endYear) = explode('-', $year);
        $fyStart = $startYear . '-04-01';
        $fyEnd = $endYear . '-03-31';

        $accounts = StoreBankAccount::where('store_id', Helpers::get_store_id())
            ->get();

        $account_last_txn =  StoreBankTransaction::where('store_id', Helpers::get_store_id())
            ->whereBetween('txn_date', [$fyStart, $fyEnd])
            ->latest('id') // tie-breaker if same date
            ->first();
        // prx($account_last_txn);
        return view('vendor-views.account.banking.bank-account.index', compact('accounts', 'fyStart', 'fyEnd', 'year', 'account_last_txn'));
    }
    public function bank_account_store(Request $request)
    {
        $request->validate([
            'bank_name' => 'required',
            'account_holder_name' => 'required',
            'account_number' => [
                'required',
                Rule::unique('store_bank_accounts')->where(function ($query) use ($request) {
                    return $query->where('store_id', Helpers::get_store_id());
                }),
            ],
            'ifsc_code' => 'required',
        ]);

        $account = new StoreBankAccount();
        $account->store_id = Helpers::get_store_id();
        $account->account_holder_name = $request->account_holder_name;
        $account->account_number = $request->account_number;
        $account->bank_name = $request->bank_name;
        $account->ifsc_code = $request->ifsc_code;
        $account->logo_folder_name = $request->logo_folder_name;
        $account->opening_balance = $request->opening_balance;
        $account->minimum_balance = $request->minimum_balance;
        $account->current_balance = $request->opening_balance;
        $account->save();

        _auditLogs('Added Bank Account : ' . $request->bank_name . '(' . $request->account_number . ')');

        Toastr::success(translate('messages.bank_account_added_successfully!'));
        return back();
    }
    public function transaction_import(Request $request)
    {
        // prx($request->all());
        $request->validate([
            'bank_account' => 'required',
            'file' => 'required|mimes:xlsx,xls,csv'
        ]);

        $extension = $request->file('file')->getClientOriginalExtension();
        $file = new StoreBankTransactionFile();
        $file->store_id = Helpers::get_store_id();
        $file->bank_id = $request->bank_account;
        $file->pdf = Helpers::upload('store/banking/', $extension, $request->file('file'));
        $file->save();

        $import = new BankTransactionImport($request->bank_account, $file->id);
        Excel::import($import, $request->file('file'));
        $bank = StoreBankAccount::find($request->bank_account);

        _auditLogs('Bank Transaction Imported : ' . $bank->bank_name . ' (' . $bank->account_number . ')');

        // if ($import->failedRows) {
        //     foreach ($import->failedRows as $key => $value) {
        //         Toastr::error($value);
        //     }
        // } else {
        Toastr::success(translate('messages.transactions_imported_successfully!'));
        // }
        return back();
    }
    public function bank_account_update(Request $request, $id)
    {
        $request->validate([
            'bank_name' => 'required',
            'account_holder_name' => 'required',
            'account_number' => [
                'required',
                Rule::unique('store_bank_accounts')
                    ->ignore($id) // ID of the record being updated
                    ->where(function ($query) use ($request) {
                        return $query->where('store_id', Helpers::get_store_id());
                    }),
            ],
            'ifsc_code' => 'required',
        ]);

        $account = StoreBankAccount::find($id);
        $account->account_holder_name = $request->account_holder_name;
        $account->account_number = $request->account_number;
        $account->bank_name = $request->bank_name;
        $account->ifsc_code = $request->ifsc_code;
        $account->logo_folder_name = $request->logo_folder_name;
        $account->minimum_balance = $request->minimum_balance;
        $account->save();

        _auditLogs('Updated Bank Account : ' . $request->bank_name . '(' . $request->account_number . ')');


        Toastr::success(translate('messages.bank_account_updated_successfully!'));
        return back();
    }
    public function bank_account_detail(Request $request, $id)
    {

        $year = $request->year ?? Helpers::get_financial_year();
        list($startYear, $endYear) = explode('-', $year);
        $fyStart = $startYear . '-04-01';
        $fyEnd = $endYear . '-03-31';

        $storeId = Helpers::get_store_id();
        $formatted_from  =  $fyStart;
        $formatted_to =  $fyEnd;

        $account = StoreBankAccount::where([
            'id' => $id,
            'store_id' => Helpers::get_store_id()
        ])->firstOrFail();

        $data['credit'] = $account->transactions()->whereBetween('txn_date', [$formatted_from, $formatted_to])->where('type', 'credit')->sum('amount');
        $data['debit'] = $account->transactions()->whereBetween('txn_date', [$formatted_from, $formatted_to])->where('type', 'debit')->sum('amount');

        $filter = $request->query('filter', 'all');
        if ($filter == 'credit') {
            $transactions = $account->transactions()->whereBetween('txn_date', [$formatted_from, $formatted_to])->where('type', 'credit')->paginate(50);
        } elseif ($filter == 'debit') {
            $transactions = $account->transactions()->whereBetween('txn_date', [$formatted_from, $formatted_to])->where('type', 'debit')->paginate(50);
        } else {
            $transactions = $account->transactions()->whereBetween('txn_date', [$formatted_from, $formatted_to])->paginate(50);
        }

        $sales = Helpers::bank_transactions_calendar($id);
        $files  = StoreBankTransactionFile::where(['store_id' => $storeId, 'bank_id' => $id])->get();
        if (!$account) {
            Toastr::error(translate('messages.bank_account_not_found!'));
            return back();
        }
        return view('vendor-views.account.banking.bank-account.detail', compact('account', 'transactions', 'data', 'sales', 'year', 'files'));
    }
    public function bank_account_detail_main(Request $request)
    {
        $storeId = Helpers::get_store_id();
 
        $year = $request->year ?? Helpers::get_financial_year();
        list($startYear, $endYear) = explode('-', $year);
        $fyStart = $startYear . '-04-01';
        $fyEnd = $endYear . '-03-31';

        $formatted_from  =  $fyStart;
        $formatted_to =  $fyEnd;

        $data['credit'] = StoreBankAccount::where('store_id', $storeId)
            ->with(['transactions' => function ($q) use ($formatted_from, $formatted_to) {
                $q->whereBetween('txn_date', [$formatted_from, $formatted_to]);
            }])
            ->get()
            ->flatMap->transactions
            ->where('type', 'credit')
            ->sum('amount');

        $data['debit'] = StoreBankAccount::where('store_id', $storeId)
            ->with(['transactions' => function ($q) use ($formatted_from, $formatted_to) {
                $q->whereBetween('txn_date', [$formatted_from, $formatted_to]);
            }])
            ->get()
            ->flatMap->transactions
            ->where('type', 'debit')
            ->sum('amount');

        // 2. Transactions across ALL accounts
        $filter = $request->query('filter', 'all');

        $transactionsQuery = StoreBankTransaction::whereHas('bankAccount', function ($q) use ($storeId) {
            $q->where('store_id', $storeId);
        })
            ->whereBetween('txn_date', [$formatted_from, $formatted_to]);

        if ($filter === 'credit') {
            $transactionsQuery->where('type', 'credit');
        } elseif ($filter === 'debit') {
            $transactionsQuery->where('type', 'debit');
        }

        $transactions = $transactionsQuery->paginate(50);

        $sales = Helpers::bank_transactions_calendar(0);

        $accounts = StoreBankAccount::where('store_id', Helpers::get_store_id())->get();

        return view('vendor-views.account.banking.bank-account.detail-main', compact(
            'transactions',
            'data',
            'sales',
            'year',
            'accounts'
        ));
    }

    public function transaction_export(Request $request, $bank_id = 'all')
    {
        $transactions = StoreBankTransaction::with('bankAccount')->where('store_id', Helpers::get_store_id())->get();
        $headings = [
            'Sl',
            'Bank Name',
            'Bank Account Number',
            'Date',
            'Narration',
            'Transaction Id',
            'Value Date',
            'Withdrawal Amt.',
            'Deposit Amt.',
            'Closing Balance',
        ];
        $rows = [];
        foreach ($transactions as $key => $txn) {
            $rows[] = [
                $key + 1,
                $txn->bankAccount?->bank_name,
                $txn->bankAccount?->account_number,
                $txn->txn_date,
                $txn->particulars,
                $txn->txn_id,
                $txn->value_date,
                $txn->type == 'debit' ? $txn->amount :  '',
                $txn->type == 'credit' ? $txn->amount :  '',
                $txn->closing_balance,
            ];
        }


        return Excel::download(new BankTransactionExport($rows, $headings), 'bank_statement.xlsx');
    }
    public function delete(Request $request, $id)
    {
        $bank_account = StoreBankAccount::where(['id' => $id, 'store_id' => Helpers::get_store_id()])->first();
        if ($bank_account) {
            _auditLogs('Bank Account Deleted : ' . $bank_account->bank_name . ' (' . $bank_account->account_number . ')');
            $bank_account->delete();
            Toastr::success(translate('messages.bank_account_deleted_successfully!'));
        } 

        return back();
    }
    public function delete_file(Request $request, $id)
    {
        $file = StoreBankTransactionFile::where(['id' => $id, 'store_id' => Helpers::get_store_id()])->first();
        if ($file) {
            $file->delete();
            Toastr::success(translate('messages.bank_transaction_file_deleted_successfully!'));
        }

        return back();
    }
}
