<?php

namespace App\Http\Controllers\Vendor;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Models\StoreAccount;
use App\Models\StoreLedgerEntry;
use Illuminate\Http\Request;

class AccountStatementController extends Controller
{
    public function trial_balance(Request $request)
    {
        $storeId = Helpers::get_store_id();
        $preset = request('date_range') ?? 'this_year';
        $custom = request('custom_date_range') ?? null;
        $range = Helpers::calculatePresetDates($preset, $custom);
        $formatted_from  = $range['start'];
        $formatted_to = $range['end'];
        $ledgerAccounts = StoreAccount::where('store_id', $storeId)
            ->where('level', 1)
            ->where('entity_type', 'store')
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
        // foreach ($ledgerAccounts as $account) {

        //     // Only own ledger, no children
        //     $ids = [$account->id];

        //     $entries = StoreLedgerEntry::whereIn('account_id', $ids);
        //         // ->whereBetween('entry_date', [$formatted_from, $formatted_to]);

        //     $account->total_debit = $entries->sum('debit');
        //     $account->total_credit = $entries->sum('credit');
        // }

        // prx($ledgerAccounts);
        return view('vendor-views.account.statement.trial-balance', compact('ledgerAccounts'));
    }
}
