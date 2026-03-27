<?php

namespace App\Http\Controllers\Admin;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Models\StoreAccount;
use App\Models\StoreLedgerEntry;
use Illuminate\Http\Request;

class AccountStatementController extends Controller
{
    public function trial_balance(Request $request)
    {
        $storeId = 0;
        $preset = request('date_range') ?? 'this_year';
        $custom = request('custom_date_range') ?? null;
        $range = Helpers::calculatePresetDates($preset, $custom);
        $formatted_from  = $range['start'];
        $formatted_to = $range['end'];
        $ledgerAccounts = StoreAccount::where('store_id', $storeId)
            ->where('level', 1)
            ->where('entity_type', 'admin')
            ->with(['ledgerEntries' => function ($q) use ($formatted_from, $formatted_to) {
                $q->whereBetween('entry_date', [$formatted_from, $formatted_to]);
            }])
            ->get();
            // prx($ledgerAccounts);

        // Now calculate totals for each account
        foreach ($ledgerAccounts as $account) {

            $ids = $account->getAllChildIds();

            $entries = StoreLedgerEntry::whereIn('account_id', $ids)
                ->whereBetween('entry_date', [$formatted_from, $formatted_to]);

            $account->total_debit = $entries->sum('debit');
            $account->total_credit = $entries->sum('credit');
        }  
   
        return view('admin-views.account.statement.trial-balance', compact('ledgerAccounts'));
    }
}
