<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Brian2694\Toastr\Facades\Toastr;
use App\CentralLogics\Helpers;
use App\Models\Department;
use App\Models\EmployeeRole;
use App\Models\LedgerAccountType;
use App\Models\RequestForm;
use App\Models\RequestFormUpdate;
use App\Models\RequestRule;
use App\Models\StoreAccount;
use App\Models\StoreConfig;
use App\Models\StoreCustomer;
use App\Models\StoreLedgerEntry;
use App\Models\StoreVoucher;
use App\Models\Vendor;
use App\Models\VendorEmpJob;
use App\Models\VendorEmployee;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View as FacadesView;
use Illuminate\View\View;

class AccountRequestFormController extends Controller
{

    public function master_ledger_rf_update(Request $request)
    {
        DB::beginTransaction();

        try {
            $id = $request->rf_id;
            $form =  RequestForm::where('id', $id)->first();

            $storeId  = Helpers::get_store_id();
            $fileName = $form->doc_file;

            if ($request->hasFile('file')) {
                $extension = $request->file->getClientOriginalExtension();
                $fileName = Helpers::upload('store/docs/', $extension, $request->file);
            }

            $user_type = auth('vendor')->check() ? 'vendor' : 'employee';

            $request_to = VendorEmployee::where('id', $request->request_to)->first();

            // credit account 
            $account = StoreAccount::where('id', $request->category)->firstOrFail();
            $method = $account->acc_type; // debit / credit

            // customer account info
            $customer  = StoreCustomer::where('id', $request->customer_id_old ?? $request->customer_id)->first();
            $customerAccount = Helpers::ensureCustomerLedger($customer);
            $customerMethod = $method == 'credit' ? 'debit' : 'credit'; // debit / credi


            if ($customerMethod == 'debit') {
                $debit_account_id = $customerAccount->id;
                $credit_account_id = $account->id;
            } else {
                $debit_account_id =  $account->id;
                $credit_account_id =  $customerAccount->id;
            }

            // update request form as pending
            $form->store_user        = $request->customer_id_old ?? $request->customer_id;
            $form->account_id        =  $request->category;
            $form->status            = 'pending'; // pending until approval
            $form->credit_account_id  = $credit_account_id;
            $form->debit_account_id  = $debit_account_id;
            $form->date              = $request->date;
            $form->gst_amount              = $request->gst_amount;
            $form->note              = $request->note;
            $form->payment_mode              = $request->payment_mode;
            $form->bill_number              = $request->bill_number;
            $form->requested_by        = $request->requested_by;
            $form->request_to        = $request->request_to;
            $form->request_to_role   = $request_to->employee_role_id ?? null;
            $form->forwarded_to      = $request->request_to;
            $form->forwarded_to_role = $request_to->employee_role_id ?? null;
            $form->description       = $request->description;
            $form->amount            = $request->amount;
            $form->doc_file          = $fileName;
            $form->created_by        = Helpers::get_loggedin_user()->id;
            $form->created_by_type   = $user_type;
            $form->save();

            // store status update 
            $remark = $request->description;
            _updateFormStatus($form->id, 'pending', $request->request_to, $remark);

            DB::commit();

            Toastr::success('Form Request updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Toastr::error('Error: ' . $e->getMessage());
        }
        return redirect()->route('vendor.account.request-form.master-ledger.index');
    }
    public function master_ledger_rf_store(Request $request)
    {
        DB::beginTransaction();
        // prx($request->all());

        try {
            $storeId  = Helpers::get_store_id();
            $fileName = null;

            if ($request->hasFile('file')) {
                $extension = $request->file->getClientOriginalExtension();
                $fileName = Helpers::upload('store/docs/', $extension, $request->file);
            }

            $user_type = auth('vendor')->check() ? 'vendor' : 'employee';

            $request_to = VendorEmployee::where('id', $request->request_to)->first();

            // credit account 
            $account = StoreAccount::where('id', $request->category)->firstOrFail();
            $method = $account->acc_type; // debit / credit

            // customer account info
            $customer  = StoreCustomer::where('id', $request->customer_id)->first();
            $customerAccount = Helpers::ensureCustomerLedger($customer);
            $customerMethod = $method == 'credit' ? 'debit' : 'credit'; // debit / credi


            if ($customerMethod == 'debit') {
                $debit_account_id = $customerAccount->id;
                $credit_account_id = $account->id;
            } else {
                $debit_account_id =  $account->id;
                $credit_account_id =  $customerAccount->id;
            }

            // debit account 


            // Save request form as pending
            $form = new RequestForm();
            $form->store_user        =  $request->customer_id;
            $form->account_id        =  $request->category;
            $form->store_id          =  $storeId;
            $form->status            = 'pending'; // pending until approval
            $form->type              = 'master_ledger';
            $form->credit_account_id  = $credit_account_id;
            $form->debit_account_id  = $debit_account_id;
            $form->date              = $request->date;
            $form->gst_amount              = $request->gst_amount;
            $form->note              = $request->note;
            $form->payment_mode              = $request->payment_mode;
            $form->bill_number              = $request->bill_number;
            $form->request_number    = _requestFormNumber();
            $form->requested_by        = $request->requested_by;
            $form->request_to        = $request->request_to;
            $form->request_to_role   = $request_to->employee_role_id ?? null;
            $form->forwarded_to      = $request->request_to;
            $form->forwarded_to_role = $request_to->employee_role_id ?? null;
            $form->description       = $request->description;
            $form->amount            = $request->amount;
            $form->doc_file          = $fileName;
            $form->created_by        = Helpers::get_loggedin_user()->id;
            $form->created_by_type   = $user_type;
            $form->save();

            // store status update 
            $remark = $request->description;
            _updateFormStatus($form->id, 'pending', $request->request_to, $remark);

            // Generate Voucher Number
            $voucherNo = Helpers::_generateVoucherNumber($storeId);

            // Save voucher as pending
            $voucher = StoreVoucher::create([
                'store_id'       => $storeId,
                'voucher_number' => $voucherNo,
                'voucher_type'   => 'Payment',
                'voucher_date'   => $request->date ?? now(),
                'total_amount'   => $request->amount,
                'narration'      => $request->description,
                'debit_entity_type' => 'store',
                'credit_entity_type' => 'employee',
                'request_no'   => $form->request_number ?? null,
                'status'         => 'pending', // pending until approved
                'created_by'     => Helpers::get_loggedin_user()->id,
                'created_by_type' => $user_type,
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);

            // Prepare entries for double-entry
            $entries = [
                // Original account entry
                [
                    'store_id'          => $storeId,
                    'voucher_type'      => $voucher->voucher_type,
                    'voucher_id'        => $voucher->id,
                    'entry_date'        => $request->date ?? $voucher->voucher_date,
                    'account_id'        => $request->category,
                    'debit'             => $method == 'debit' ? $request->amount : 0.00,
                    'credit'            => $method == 'credit' ? $request->amount : 0.00,
                    'narration'         => $request->description,
                    'request_no'        => $voucher->request_no,
                    'status'            => 'pending',
                    'gst_amount'        => $request->gst_amount,
                    'payment_mode'      => $request->payment_mode,
                    'note'              => $request->note,
                    'document'          => $fileName,
                    'created_by'        => Helpers::get_loggedin_user()->id,
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
                    'status'            => 'pending',
                    'gst_amount'        => $request->gst_amount,
                    'payment_mode'      => $request->payment_mode,
                    'note'              => $request->note,
                    'document'          => $fileName,
                    'created_by'        => Helpers::get_loggedin_user()->id,
                    'created_by_type'   => $user_type,
                    'created_at'        => now(),
                    'updated_at'        => now(),
                ],
            ];
            StoreLedgerEntry::insert($entries);
            DB::commit();

            _auditLogs('Created Master Ldeger Request Form');

            Toastr::success('Request submitted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Toastr::error('Error: ' . $e->getMessage());
        }
        return back();
    }
    public function master_ledger(Request $request, $id = null, $tab = null)
    {
        $edit = $voucher = null;
        if ($id) {
            $edit = RequestForm::with('customer')->where('id', $id)->first();
            $voucher = StoreVoucher::where('request_no', $edit->request_number)->first();
        }

        $user_type = auth('vendor')->check() ? 'vendor' : 'employee';
        $storeId  = Helpers::get_store_id();
        $permitted_resubmit = StoreConfig::where('store_id', $storeId)->first();
        $permitted_resubmit = $permitted_resubmit ? $permitted_resubmit->resubmit_per_req_form : 0;
        $voucherNo =  Helpers::_generateVoucherNumber($storeId);
        $employees = VendorEmployee::where('store_id', $storeId)->get();
        $data['submitted_req'] = RequestForm::with(['requestedBy', 'requestedTo'])->where('type', 'master_ledger')->where(function ($query) use ($storeId, $user_type) {
            $query->where('requested_by', Helpers::get_loggedin_user()->id)
                ->orWhere(function ($q) use ($user_type) {
                    $q->where('created_by', Helpers::get_loggedin_user()->id)
                        ->where('created_by_type', $user_type);
                });
        })
            ->where('store_id', $storeId)
            ->get();
        return view('vendor-views.account.request-form.master-ledger', compact('tab', 'edit', 'voucher', 'permitted_resubmit', 'voucherNo', 'employees', 'data'));
    }
    public function journal_entry_rf_store(Request $request)
    {
        DB::beginTransaction();
        // prx($request->all());

        try {
            $storeId  = Helpers::get_store_id();
            $fileName = null;

            if ($request->hasFile('doc_file')) {
                $extension = $request->doc_file->getClientOriginalExtension();
                $fileName = Helpers::upload('store/documents/', $extension, $request->doc_file);
            }

            $user_type = auth('vendor')->check() ? 'vendor' : 'employee';

            $request_to = VendorEmployee::where('id', $request->request_to)->first();
            // Save request form as pending
            $form = new RequestForm();
            $form->store_id          = $storeId;
            $form->status            = 'pending'; // pending until approval
            $form->type              = 'journal_entry';
            $form->requested_by      = $request->requested_by;
            $form->date              = $request->date;
            $form->request_number    = _requestFormNumber();
            $form->request_to        = $request->request_to;
            $form->request_to_role   = $request_to->employee_role_id ?? null;
            $form->forwarded_to      = $request->request_to;
            $form->forwarded_to_role = $request_to->employee_role_id ?? null;
            $form->ledger_type       = $request->ledger_type;
            $form->description       = $request->description;
            $form->amount            = $request->amount;
            $form->doc_file          = $fileName;
            $form->created_by        = Helpers::get_loggedin_user()->id;
            $form->created_by_type   = $user_type;
            $form->save();

            // store status update 
            $remark = $request->description;
            _updateFormStatus($form->id, 'pending', $request->request_to, $remark);

            // Generate Voucher Number
            $voucherNo = Helpers::_generateVoucherNumber($storeId);

            // Save voucher as pending
            $voucher = StoreVoucher::create([
                'store_id'       => $storeId,
                'voucher_number' => $voucherNo,
                'voucher_type'   => 'Payment',
                'voucher_date'   => $request->date ?? now(),
                'total_amount'   => $request->amount,
                'narration'      => $request->description,
                'debit_entity_type' => 'store',
                'credit_entity_type' => 'emoployee',
                'request_no'   => $form->request_number ?? null,
                'status'         => 'pending', // pending until approved
                'created_by'     => Helpers::get_loggedin_user()->id,
                'created_by_type' => $user_type,
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);

            // Credit Entry (Cash/Bank / Employee)
            $employee = VendorEmployee::find($request->requested_by);
            $employee_ledger_account = Helpers::ensureEmployeeLedger($employee);

            StoreLedgerEntry::create([
                'store_id'       => $voucher->store_id,
                'voucher_type'   => $voucher->voucher_type,
                'voucher_id'     => $voucher->id,
                'entry_date'     => $voucher->voucher_date,
                'account_id'     => $employee_ledger_account?->id,
                'debit'          => 0.00,
                'credit'         => $request->amount,
                'narration'      => $request->description,
                'request_no'   => $voucher->request_no,
                'status'         => 'pending', // <-- pending
                'created_by'     => Helpers::get_loggedin_user()->id,
                'created_by_type' => $user_type,
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);

            DB::commit();
            _auditLogs('Submitted journal entry request form');

            Toastr::success('Request submitted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Toastr::error('Error: ' . $e->getMessage());
        }
        return back();
    }
    public function journal_entry_rf_update(Request $request)
    {
        // prx('journal_entry_rf_update');
        $id = $request->rf_id;
        $storeId = Helpers::get_store_id();
        DB::beginTransaction();
        $form =  RequestForm::find($id);

        // prx($form);
        try {
            $fileName = $form->doc_file;

            if ($request->hasFile('doc_file')) {
                $extension = $request->doc_file->getClientOriginalExtension();
                $fileName = Helpers::upload('store/documents/', $extension, $request->doc_file);
            }

            $user_type = auth('vendor')->check() ? 'vendor' : 'employee';

            $request_to = VendorEmployee::where('id', $request->request_to)->first();
            // Save request form as pending
            $form->status            = 'resubmit'; // pending until approval
            $form->requested_by      = $request->requested_by;
            $form->date              = $request->date;
            $form->request_to        = $request->request_to;
            $form->request_to_role   = $request_to->employee_role_id ?? null;
            $form->forwarded_to      = $request->request_to;
            $form->forwarded_to_role = $request_to->employee_role_id ?? null;
            $form->ledger_type       = $request->ledger_type;
            $form->description       = $request->description;
            $form->amount            = $request->amount;
            $form->doc_file          = $fileName;
            $form->resubmit          = $form->resubmit + 1;
            $form->created_by        = Helpers::get_loggedin_user()->id;
            $form->created_by_type   = $user_type;
            $form->save();

            // store status update 
            $remark =  $request->description;
            _updateFormStatus($form->id, 'resubmit', $request->request_to, $remark);

            // update ledger
            $credit_entry = StoreLedgerEntry::where('store_id', $storeId)->where('request_no', $form->request_number)->first();

            if ($credit_entry) {
                $voucher = StoreVoucher::where("id", $credit_entry->voucher_id)->first();

                $voucher->total_amount = $request->amount;
                $voucher->save();

                $credit_entry->credit = $request->amount;
                $credit_entry->save();
            }

            DB::commit();
            _auditLogs('Resubmitted  ' . translate($form->type) . ' Request');

            Toastr::success('Updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Toastr::error('Error: ' . $e->getMessage());
        }
        return redirect()->route('vendor.account.request-form.journal-entry.index');
    }

    public function journal_entry(Request $request, $id = null)
    {

        $storeId = Helpers::get_store_id();
        $permitted_resubmit = StoreConfig::where('store_id', $storeId)->first();
        $permitted_resubmit = $permitted_resubmit ? $permitted_resubmit->resubmit_per_req_form : 0;
        $edit = null;
        if ($id) {
            $edit = RequestForm::find($id);
        }
        $user_type = auth('vendor')->check() ? 'vendor' : 'employee';
        $employees = VendorEmployee::where('store_id', $storeId)->get();
        $departments = Department::where('vendor_id', $storeId)->get();
        $ledger_types = StoreAccount::where('store_id', $storeId)->get();
        $request_number = _requestFormNumber();
        $data['submitted_req'] = RequestForm::with(['requestedBy', 'requestedTo'])->where('type', 'journal_entry')->where(function ($query) use ($storeId, $user_type) {
            $query->where('requested_by', Helpers::get_loggedin_user()->id)
                ->orWhere(function ($q) use ($user_type) {
                    $q->where('created_by', Helpers::get_loggedin_user()->id)
                        ->where('created_by_type', $user_type);
                });
        })

            ->where('store_id', $storeId)
            ->get();
        // prx($submitted_req);
        return view('vendor-views.account.request-form.journal-entry', compact('edit', 'permitted_resubmit', 'data', 'employees', 'departments', 'ledger_types', 'request_number'));
    }

    public function  approvals()
    {
        $storeId = Helpers::get_store_id();
        $rules = RequestRule::with(['role', 'department'])->where('store_id', $storeId)->get();

        $allDepIds  = $rules->pluck('send_to_dep_id')->flatten()->unique();
        $allRoleIds = $rules->pluck('send_to_role_id')->flatten()->unique();
        $allEmpIds  = $rules->pluck('employee_id')->flatten()->unique();

        $deps  = Department::whereIn('id', $allDepIds)->get()->keyBy('id');
        $roles = EmployeeRole::whereIn('id', $allRoleIds)->get()->keyBy('id');
        $emps  = VendorEmployee::whereIn('id', $allEmpIds)->get()->keyBy('id');

        $departments = Department::where('vendor_id', $storeId)->get();
        $roles = EmployeeRole::where('store_id', $storeId)->get();
        $employees = VendorEmployee::where('store_id', $storeId)->where('status', 1)->get();
        $rule = RequestRule::where('id', 24)->where('store_id', Helpers::get_store_id())->first();
        // prx($rule->permissions);
        return view('vendor-views.account.approvals', compact('rules', 'departments', 'roles', 'employees', 'rule'));
    }

    public function request_rule_edit(Request $request, $id)
    {
        $storeId = Helpers::get_store_id();
        $rule = RequestRule::where('id', $id)->where('store_id', Helpers::get_store_id())->first();

        $departments = Department::where('vendor_id', $storeId)->get();
        $roles = EmployeeRole::where('store_id', $storeId)->get();
        $employees = VendorEmployee::where('store_id', $storeId)->where('status', 1)->get();

        return view('vendor-views.account.approval-edit', compact('rule', 'departments', 'roles', 'employees'));


        // $html = FacadesView::make('vendor-views.account.approval-edit', compact('rule', 'departments', 'roles', 'employees'));
        // return $html;
    }
    public function request_rule_delete(Request $request, $id)
    {

        $rule = RequestRule::where('id', $id)->where('store_id', Helpers::get_store_id())->first();
        _auditLogs('Delete Request Rule : ID :' . $rule->id);
        $rule->delete();

        Toastr::success('Request Rule Deleted Successfully');
        return back();
    }
    public function request_rule_update(Request $request)
    {
        $id = $request->id;
        $validator = Validator::make($request->all(), [
            'role_id'       => 'required',
            'department_id' => 'required',
            'form_type'     => 'required',
            'permissions'   => 'required|array|min:1',

            'send_to_role_id' => 'nullable|array',
            'send_to_dep_id'  => 'nullable|array',
            'employee_id'     => 'nullable|array',
        ]);

        // Forwarding validation only if "forward" is selected in permissions
        $validator->sometimes(
            ['send_to_role_id', 'send_to_dep_id', 'employee_id'],
            'required_without_all:send_to_role_id,send_to_dep_id,employee_id',
            function ($input) {
                return in_array('forward', $input->permissions ?? []);
            }
        );

        $validator->validate();

        $storeId = Helpers::get_store_id();

        // Check uniqueness (exclude current record)
        $exists = RequestRule::where('store_id', $storeId)
            ->where('form_type', $request->form_type)
            ->where('department_id', $request->department_id)
            ->where('role_id', $request->role_id)
            ->where('id', '!=', $id)  // ← important
            ->exists();

        if ($exists) {
            return back()->withErrors([
                'role_id' => 'A rule with this combination already exists.',
            ])->withInput();
        }

        // Update rule
        $rule = RequestRule::findOrFail($id);
        $rule->form_type     = $request->form_type;
        $rule->department_id = $request->department_id;
        $rule->role_id       = $request->role_id;
        $rule->permissions   = $request->permissions;
        $rule->send_to_dep_id       = $request->send_to_dep_id;
        $rule->send_to_role_id      = $request->send_to_role_id;
        $rule->send_to_employee_id  = $request->employee_id;
        $rule->save();

        _auditLogs('Updated Request Rule');

        Toastr::success('Request Rule Updated Successfully');
        return back();
    }

    public function request_rule_store(Request $request)
    {
        // prx($request->all());
        $validator = Validator::make($request->all(), [
            'role_id'       => 'required',
            'department_id' => 'required',
            'form_type'     => 'required',
            'permissions'   => 'required|array|min:1',

            'send_to_role_id' => 'nullable|array',
            'send_to_dep_id'  => 'nullable|array',
            'employee_id'     => 'nullable|array',
        ], [
            'send_to_role_id.required_without_all' => 'One of Role, Department, or Employee is required when forwarding.',
            'send_to_dep_id.required_without_all'  => 'One of Role, Department, or Employee is required when forwarding.',
            'employee_id.required_without_all'     => 'One of Role, Department, or Employee is required when forwarding.',
        ]);

        $validator->sometimes(
            ['send_to_role_id', 'send_to_dep_id', 'employee_id'],
            'required_without_all:send_to_role_id,send_to_dep_id,employee_id',
            function ($input) {
                return in_array('forward', $input->permissions ?? []);
            }
        );

        $validator->validate();


        $storeId = Helpers::get_store_id();

        // Check uniqueness
        $exists = RequestRule::where('store_id', $storeId)
            ->where('form_type', $request->form_type)
            ->where('department_id', $request->department_id)
            ->where('role_id', $request->role_id)
            ->exists();

        if ($exists) {
            return back()->withErrors([
                'role_id' => 'A rule with this combination already exists.',
            ])->withInput();
        }

        // Save new rule
        $rule = new RequestRule();
        $rule->store_id      = $storeId;
        $rule->form_type     = $request->form_type;
        $rule->department_id = $request->department_id;
        $rule->role_id       = $request->role_id;
        $rule->permissions   = $request->permissions;
        $rule->send_to_dep_id  = $request->send_to_dep_id;
        $rule->send_to_role_id = $request->send_to_role_id;
        $rule->send_to_employee_id     = $request->employee_id;
        $rule->save();

        _auditLogs('Created New Request Rule');

        // fetch : whereJsonContains 
        Toastr::success('Request Rule Created Successfully');
        return back();
    }

    public function  incoming_requests()
    {
        $storeId = Helpers::get_store_id();
        if (auth('vendor_employee')->check()) {
            $employee = Helpers::get_loggedin_user();
            $employee_id = $employee->id;
            $role = $employee->employee_role_id;
            $data['submitted_req'] = RequestForm::with(['requestedBy', 'requestedTo', 'forwardedTo', 'creditAccount', 'debitAccount'])
                ->where(function ($q) use ($employee_id, $role) {
                    $q->where('forwarded_to', $employee_id)
                        ->orWhere('forwarded_to_role', $role);
                })
                ->where('store_id', $storeId)
                ->get();
            $data['expense_accounts'] = StoreAccount::where('ledger_account_type_id', 1)->where('store_id', $storeId)->whereNull('parent_id')->get();
            return view('vendor-views.account.request-form.incoming_requests', compact('data'));
        }
    }
    public function incoming_rf_details(Request $request, $id)
    {
        $vendor_id = Helpers::get_store_data()->vendor_id;
        $vendor = Vendor::where('id', $vendor_id)->first();
        $rf = RequestForm::with(['requestedBy', 'requestedTo', 'createdBy'])->where('id', $id)->first();
        $statusHistory  = RequestFormUpdate::with(['updatedBy', 'sentTo'])->where("request_form_id", $id)->get();
        return view("vendor-views.account.request-form.rf_details", compact('vendor', 'statusHistory', 'rf'));
    }
    public function  incoming_request_forward(Request $request)
    {
        $storeId = Helpers::get_store_id();
        $req = RequestForm::where('store_id', $storeId)->where('id', $request->request_id)->firstOrFail();

        $remark = $request->remark ?? '';
        _updateFormStatus($request->request_id, $request->request_status, $request->sent_to, $remark);

        $sent_to = VendorEmployee::where('store_id', $storeId)->where('id', $request->sent_to)->first();

        $req->status = $request->request_status;
        $req->forwarded_to = $request->sent_to;
        $req->forwarded_to_role = $sent_to->employee_role_id;
        $req->save();

        _auditLogs('Forwarded  ' . translate($req->type) . ' Request');


        Toastr::success(ucfirst($request->request_status) . 'ed Successfully');
        return back();
    }
    public function  incoming_requests_reject(Request $request, $id)
    {
        // is required
        // $request->validate([
        //     'account_id' => 'required',
        // ]);
        $status = 'rejected';

        $storeId  = Helpers::get_store_id();
        $user_type = auth('vendor')->check() ? 'vendor' : 'employee';

        $req = RequestForm::where('id', $id)->where('store_id', $storeId)->first();
        $req->status = $status;
        $req->status_update_by = Helpers::get_loggedin_user()->id;
        $req->status_update_by_type = $user_type;
        $req->save();

        $permitted_resubmit = StoreConfig::where('store_id', $storeId)->first();

        $permitted_resubmit = $permitted_resubmit ? $permitted_resubmit->resubmit_per_req_form : 0;

        $remark = $request->remark ?? '';
        _updateFormStatus($id, 'rejected', null, $remark);

        $credit_entry = StoreLedgerEntry::where('store_id', $storeId)->where('request_no', $req->request_number)->first();

        if ($credit_entry) {
            $voucher = StoreVoucher::where("id", $credit_entry->voucher_id)->first();

            try {

                if ($permitted_resubmit == $req->resubmit) {
                    $voucher->status = 'rejected';
                    $voucher->save();

                    $credit_entry->status = 'rejected';
                    $credit_entry->save();
                }

                Toastr::success(ucfirst($status) . ' Successfully');
            } catch (\Exception $e) {
                //   echo  $e->getMessage();
                Toastr::error("Some Error Occured");
            }
        }
        _auditLogs('Rejected ' . translate($req->type) . ' Request');

        return back();
    }
    public function  incoming_requests_close(Request $request)
    {
        // is required
        $request->validate([
            'account_id' => 'required',
        ]);
        $id = $request->request_id;
        $status = 'closed';

        $storeId  = Helpers::get_store_id();
        $user_type = auth('vendor')->check() ? 'vendor' : 'employee';

        $req = RequestForm::where('id', $id)->where('store_id', $storeId)->first();
        $req->status = $status;
        $req->status_update_by = Helpers::get_loggedin_user()->id;
        $req->status_update_by_type = $user_type;
        $req->save();

        $remark = $request->remark ?? '';
        _updateFormStatus($id, $status, null, $remark);

        // Get existing ledger entries for this request
        $ledgerEntries = StoreLedgerEntry::where('store_id', $storeId)
            ->where('request_no', $req->request_number)
            ->get();

        // Find if credit/debit entries already exist
        $creditEntry = $ledgerEntries->where('credit', '>', 0)->first();
        $debitEntry = $ledgerEntries->where('debit', '>', 0)->first();

        $voucher = null;
        if ($creditEntry) {
            $voucher = StoreVoucher::find($creditEntry->voucher_id);
        }
        $amount = null;
        $action = null;
        // Create missing debit entry
        if (!$debitEntry && $creditEntry) {
            StoreLedgerEntry::create([
                'store_id'        => $storeId,
                'voucher_type'    => $voucher->voucher_type,
                'voucher_id'      => $voucher->id,
                'entry_date'      => $voucher->voucher_date,
                'account_id'      => $request->account_id, // dynamic debit account
                'debit'           => $creditEntry->credit,
                'credit'          => 0.00,
                'narration'       => $creditEntry->description,
                'request_no'      => $creditEntry->request_no,
                'status'          => 'approved',
                'created_by'      => Helpers::get_loggedin_user()->id,
                'created_by_type' => $user_type,
                'completed_at' => now(),
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);
            $amount = $creditEntry->credit;
            $action = 'debit';
        }

        // Create missing credit entry
        if (!$creditEntry && $debitEntry) {
            StoreLedgerEntry::create([
                'store_id'        => $storeId,
                'voucher_type'    => $debitEntry->voucher_type,
                'voucher_id'      => $debitEntry->voucher_id,
                'entry_date'      => $debitEntry->entry_date,
                'account_id'      => $request->account_id, // dynamic credit account
                'debit'           => 0.00,
                'credit'          => $debitEntry->debit,
                'narration'       => $debitEntry->narration,
                'request_no'      => $debitEntry->request_no,
                'status'          => 'approved',
                'created_by'      => Helpers::get_loggedin_user()->id,
                'created_by_type' => $user_type,
                'completed_at' => now(),
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);
            $amount = $debitEntry->debit;
            $action = 'credit';
        }

        // Mark existing entries as approved
        foreach ($ledgerEntries as $entry) {
            $entry->status = 'approved';
            $entry->save();
        }

        // Update voucher status
        if ($voucher) {
            $voucher->status = 'approved';
            $voucher->completed_at = now();
            $voucher->save();
        }

        if ($voucher && $amount && $action) {
            _saveDayBookEntry($amount, $action, $storeId, $voucher->narration, null, null);
        } elseif ($voucher && $creditEntry && $debitEntry) {
            $amount = $creditEntry->credit ?: $debitEntry->debit;
            $action = $creditEntry && $creditEntry->credit > 0 ? 'credit' : 'debit';
            _saveDayBookEntry($amount, $action, $storeId, $voucher->narration, null, $voucher->id);
        }
        _auditLogs('Closed  ' . translate($req->type) . ' Request');

        Toastr::success(ucfirst($status) . ' Successfully');
        return back();
    }
}
