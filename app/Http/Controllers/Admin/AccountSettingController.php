<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Brian2694\Toastr\Facades\Toastr;
use App\CentralLogics\Helpers;
use App\Models\Account;
use App\Models\AccountOption;
use App\Models\LedgerAccountType;
use App\Models\StoreAccount;
use App\Models\StoreConfig;

class AccountSettingController extends Controller
{

    public function chart_of_account(Request $request)
    {
        $ledger_account_types = LedgerAccountType::with('adminAccount')->get();
        // prx($ledger_account_types);
        return view('admin-views.account.setting.chart_of_account', compact('ledger_account_types'));
    }
    public function chart_of_account_detail(Request $request, $parent = null, $ledger_account_type = null)
    {
        $ledger_account = LedgerAccountType::where("id", $ledger_account_type)->first();
        $storeId = 0;
        $parentAccount = null;
        if ($parent) {
            $parentAccount = StoreAccount::where('id', $parent)->first();
        }

        $storeAccounts = StoreAccount::where('store_id', $storeId)
            ->when($parent, function ($query, $parent) {
                return $query->where('parent_id', $parent);
            }, function ($query) {
                return $query->whereNull('parent_id');
            })
            ->when($ledger_account_type, function ($query, $ledger_account_type) {
                return $query->where('ledger_account_type_id', $ledger_account_type);
            })
            ->get();



        $ledger_account_types = LedgerAccountType::with('adminAccount')->get();
        return view('admin-views.account.setting.chart_of_account_detail', compact('storeAccounts', 'parentAccount', 'ledger_account'));
    }
    public function account_store(Request $request)
    {
        // prx($request->all());
        $storeId = 0;
        $request->validate([
            'name' => "required|string|unique:store_accounts,name,NULL,id,store_id,$storeId",
            'ledger_account_type_id' => 'required_without:parent_id|exists:ledger_account_types,id',
            'parent_id' => 'nullable|exists:store_accounts,id',
        ], [
            'name.unique' => 'This account name already exists.',
        ]);

        $parentId = $request->parent_id;

        // calculate level
        if ($parentId) {
            $parent = StoreAccount::where('store_id', $storeId)->findOrFail($parentId);
            $level = $parent->level + 1;
            $ledger_account_type_id = $parent->ledger_account_type_id;

            if ($level != 2 && $request->account_type == 'cost_center') {
                Toastr::error('Cost centers can only be added under level 1 accounts.');
                return back();
            }

            if ($request->account_type == 'cost_center') {
                $costCenterCount = StoreAccount::where('store_id', $storeId)
                    ->where('parent_id', $parentId)
                    ->where('account_type', 'cost_center')
                    ->count();

                if ($costCenterCount >= 2) {
                    Toastr::error('This account already has 2 cost centers.');
                    return back();
                }
            }
        } else {
            $level = 1;
            $ledger_account_type_id = $request->ledger_account_type_id;

            if ($request->account_type == 'cost_center') {
                Toastr::error('Cost centers cannot be added at root level.');
                return back();
            }
        }

        // cost center limit check on this level
        // $costCenterCount =

        $account = StoreAccount::create([
            'store_id' => $storeId,
            'account_type' => $request->account_type ?? 'common',
            'acc_type' => $request->acc_type ?? 'debit',
            'ledger_account_type_id' => $ledger_account_type_id,
            'parent_id' => $parentId ?? null,
            'code' => _accountCode($ledger_account_type_id, $parentId),
            'name' => $request->name,
            'description' => $request->description,
            'level' => $level,
            'self_added' => 1,
            'entity_type' => 'admin'
        ]);

        Toastr::success('Added Successfully');
        return redirect()->back();
    }


    public function account_update(Request $request)
    {
        $id = $request->id;
        $storeId = 0;

        $account = StoreAccount::where('store_id', $storeId)->findOrFail($id);

        $request->validate([
            'name' => "required|string|unique:store_accounts,name,$id,id,store_id,$storeId",
        ], [
            'name.unique' => 'This account name already exists.',
        ]);

        $account->update([
            'name' => $request->name,
            'description' => $request->description,
        ]);

        Toastr::success('Updated Successfully');
        return redirect()->back();
    }

    public function account_delete(Request $request, $id)
    {
        StoreAccount::find($id)->delete();
        Toastr::success(translate('messages.store_account_delete'));
        return back();
    }
    public function update(Request $request)
    {
        $store_id = 0;
        if ($request->has('account_type')) {
            StoreConfig::updateOrInsert(['store_id' => $store_id], [
                'account_type' => $request->account_type,
            ]);
        }
        _auditLogs('Account Type Settings Updated');

        Toastr::success(translate('messages.updated_successfully'));
        return back();
    }
    public function common_settings(Request $request)
    {
        $storeId = 0;
        $storeConfig = StoreConfig::where('store_id', $storeId)->first();
        $expense_types = AccountOption::where('type', 'expense_type')->where('store_id', $storeId)->get();
        return view('admin-views.account.setting.common_settings', compact('storeConfig', 'expense_types'));
    }
    public function account_option_delete(Request $request, $id)
    {
        $storeId = 0;

        AccountOption::where('store_id', $storeId)->where('id', $id)->delete();
        Toastr::success("Deleted Successfully");
        return back();
    }
    public function account_option_update(Request $request)
    {
        $storeId = 0;

        $account = AccountOption::where('store_id', $storeId)->where('id', $request->id)->first();
        $account->name = $request->name;
        $account->save();

        Toastr::success("Updated Successfully");
        return back();
    }
    public function all_update(Request $request) //settings
    {
        $store_id = 0;
        if ($request->has('resubmit_per_req_form')) {
            StoreConfig::updateOrInsert(['store_id' => $store_id], [
                'resubmit_per_req_form' => $request->resubmit_per_req_form,
            ]);
        }
        if ($request->has('monthly_maintnnce_req')) {
            StoreConfig::updateOrInsert(['store_id' => $store_id], [
                'monthly_maintnnce_req' => $request->monthly_maintnnce_req,
            ]);
        }
        _auditLogs('Account Common Settings Updated');
        Toastr::success(translate('messages.updated_successfully'));
        return back();
    }
}
