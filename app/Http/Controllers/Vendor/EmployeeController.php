<?php

namespace App\Http\Controllers\Vendor;

use Illuminate\Support\Facades\File;
use App\Models\EmployeeRole;
use Illuminate\Http\Request;
use App\CentralLogics\Helpers;
use App\Models\VendorEmployee;
use App\Models\Department;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Brian2694\Toastr\Facades\Toastr;
use Maatwebsite\Excel\Facades\Excel;
use Rap2hpoutre\FastExcel\FastExcel;
use App\Exports\StoreEmployeeListExport;
use App\Models\AcceptedServiceRequest;
use App\Models\AdvanceRequest;
use App\Models\AssetAlotment;
use App\Models\LedgerAccountType;
use App\Models\Project;
use App\Models\StoreAccount;
use App\Models\StoreEmployeeComment;
use App\Models\StoreTask;
use App\Models\VendorEmpJob;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;

class EmployeeController extends Controller
{

    public function add_new()
    {
        $v_id = Helpers::get_store_id();
        $departments = Department::where('vendor_id', Helpers::get_store_id())->where('status', '1')->get();
        $rls = EmployeeRole::where('store_id', Helpers::get_store_id())->get();
        return view('vendor-views.employee.add-new', compact('rls', 'departments'));
    }
    public function resignation_action(Request $request, $id, $action)
    {
        $rr = DB::table('employee_resignations')
            ->where('id', $id)
            ->where('store_id', Helpers::get_store_id())
            ->first();

        if (!$rr) {
            Toastr::error('Resignation request not found');
            return back();
        }

        DB::table('employee_resignations')
            ->where('id', $id)
            ->update(['status' => $action, 'updated_at' => now()]);

        if ($action == 'approved') {
            $emp = VendorEmployee::find($rr->employee_id);
            if ($emp) {
                $emp->status = 0;
                $emp->resignation = 1;
                $emp->resigned_email = $emp->email;
                $emp->resigned_phone = $emp->phone;
                $emp->email = null;
                $emp->phone = null;
                $emp->save();
            }
        }

        Toastr::success(ucfirst($action) . ' successfully');
        return back();
    }
    public function comment_save(Request $request)
    {
        // prx($request->all());
        $comment = new StoreEmployeeComment();
        $comment->employee_id =  $request->staff_id;
        $comment->store_id = Helpers::get_store_id();
        $comment->comment = $request->comment;
        $comment->save();

        Toastr::success('Comment Added Successfully');
        return back();
    }
    public function resign(Request $request)
    {
        //check  password 
        $request->validate([
            // 'password' => 'required|string',
            'reason' => 'required|string',
        ]);

        // Get current logged-in vendor employee
        $user = auth('vendor_employee')->user();

        // if (!$user) {
        //     Toastr::error('Not Authenticated');
        //     return back();
        // }
        // if (!Hash::check($request->password, $user->password)) {
        //     Toastr::error('Incorrect Password');
        //     return back();
        // }

        $empId = Helpers::get_loggedin_user()->id;
        $storeId = Helpers::get_store_id();

        // Block if already has a pending or approved resignation
        $existing = DB::table('employee_resignations')
            ->where('employee_id', $empId)
            ->where('store_id', $storeId)
            ->whereIn('status', ['pending', 'approved'])
            ->first();

        if ($existing) {
            $msg = $existing->status === 'approved'
                ? 'Your resignation has already been approved.'
                : 'You already have a pending resignation request.';
            Toastr::warning($msg);
            return back();
        }

        $resignationData = [
            'employee_id' => $empId,
            'store_id'    => $storeId,
            'reason'      => $request->reason,
            'status'      => 'pending',
            'created_at'  => now(),
            'updated_at'  => now(),
        ];

        DB::table('employee_resignations')->insert($resignationData);

        Toastr::success('Resignation submitted successfully. Awaiting approval.');
        return back();
    }
    public function terminate(Request $request, $id)
    {
        $emp = VendorEmployee::where('store_id', Helpers::get_store_id())->where('id', $id)->first();
        $emp->terminate = 1;
        $emp->status = 0;
        $emp->resigned_email = $emp->email;
        $emp->resigned_phone = $emp->phone;
        $emp->email = null;
        $emp->phone = null;
        $emp->save();

        Toastr::success('Terminated Employee');
        return back();
    }

    public function store(Request $request)
    {
        // prx($request->all());
        $request->validate([
            'f_name' => 'required',
            'role_id' => 'required',
            'department_id' => 'required',
            'image' => 'required',
            'email' => 'required|unique:vendor_employees',
            'phone' => 'required|regex:/^([0-9\s\-\+\(\)]*)$/|max:20|unique:vendor_employees',
            'password' => ['required', Password::min(8)->mixedCase()->letters()->numbers()->symbols()],
        ]);

        $documents = [];
        if (!empty($request->file('documents'))) {
            foreach ($request->documents as $file) {
                $extension = $file->getClientOriginalExtension();
                $doc_name = Helpers::upload('vendor/documents/', $extension, $file);
                $documents[] = $doc_name;
            }
        }

        if ($request->ra_address1) {
            $residential_address = [
                'ra_email' =>  $request->ra_email ?? null,
                'ra_phone' =>  $request->ra_phone,
                'ra_address1' =>  $request->ra_address1,
                'ra_address2' =>  $request->ra_address2,
                'ra_pincode' =>  $request->ra_pincode,
                'ra_city' =>  $request->ra_city,
                'ra_state' =>  $request->ra_state
            ];
        }
        if ($request->pa_address1) {
            $permanent_address = [
                'pa_email' =>  $request->pa_email ?? null,
                'pa_phone' =>  $request->pa_phone,
                'pa_address1' =>  $request->pa_address1,
                'pa_address2' =>  $request->pa_address2,
                'pa_pincode' =>  $request->pa_pincode,
                'pa_city' =>  $request->pa_city,
                'pa_state' =>  $request->pa_state
            ];
        }
        $education = [];
        if ($request->has('school_name') && $request->school_name[0]) {
            foreach ($request->school_name as $key => $value) {
                $education[] = [
                    'school_name' => $request->school_name[$key],
                    'degree_diploma' => $request->degree_diploma[$key],
                    'field_of_study' => $request->field_of_study[$key],
                    'start_month' => $request->start_month[$key],
                    'end_month' => $request->end_month[$key],
                    'additional_notes' => $request->additional_notes[$key],
                ];
            }
        }
        $experience = [];
        if ($request->has('occupation') && $request->occupation[0]) {
            foreach ($request->occupation as $key => $value) {
                if ($request->hasFile("experience_letter.$key")) {
                    $file = $request->file("experience_letter.$key");
                    $extension = $file->getClientOriginalExtension();
                    $experience_letter = Helpers::upload('vendor/documents/', $extension, $file);
                }
                $experience[] = [
                    'occupation' => $request->occupation[$key],
                    'company_name' => $request->company_name[$key],
                    'summary' => $request->summary[$key],
                    'exp_start_date' => $request->exp_start_date[$key] ?? null,
                    'currently_working' => $request->currently_working[$key] ?? null,
                    'exp_end_date' => $request->exp_end_date[$key] ?? null,
                    'exp_letter' => $experience_letter ?? null,
                ];
            }
        }
        $documentation = [];

        if ($request->has('doc_name')) {
            foreach ($request->doc_name as $key => $value) {
                if ($request->doc_number[$key] || $request->doc_name[$key]) {
                    if ($request->hasFile("doc_file.$key")) {
                        $file = $request->file("doc_file.$key");
                        $extension = $file->getClientOriginalExtension();
                        $doc_file = Helpers::upload('vendor/documents/', $extension, $file);
                    }
                    $documentation[] = [
                        'doc_name' => $request->doc_name[$key] ?? null,
                        'doc_number' => $request->doc_number[$key],
                        'doc_file' => $doc_file ??  null,
                    ];
                    $doc_file = null; // resetting
                }
            }
        }

        if ($request->file('offer_letter')) {
            $file = $request->file('offer_letter');
            $extension = $file->getClientOriginalExtension();
            $offer_letter = Helpers::upload('vendor/documents/', $extension, $file);
        }

        if ($request->file('id_document')) {
            $file = $request->file('id_document');
            $extension = $file->getClientOriginalExtension();
            $id_document = Helpers::upload('vendor/documents/', $extension, $file);
        }

        // emergency contact details
        if (
            $request->has('emergency_contact_name') && $request->emergency_contact_name &&
            $request->has('emergency_contact_phone') && $request->emergency_contact_phone
        ) {
            $emergency_phone = $request->emergency_contact_phone;
            $emergency_contact_details = [
                'name' => $request->emergency_contact_name,
                'relationship' => $request->emergency_contact_relationship,
                'phone' => $request->emergency_contact_phone,
                'alt_phone' => $request->emergency_contact_alt_phone,
                'language' => $request->emergency_contact_language,
                'address' => $request->emergency_contact_address,
            ];
        }


        $employeeId =  DB::table('vendor_employees')->insertGetId([
            'employee_id' => _newEmpId(true),
            'store_shift_id' => $request->shift,
            'branch_id' => $request->branch_id,
            'f_name' => $request->f_name,
            'l_name' => $request->l_name,
            'phone' => $request->phone,
            'email' => $request->email,
            'dob' => $request->dob,
            'skills' => ($request->has('skills') && count($request->skills)) ? implode(',', $request->skills) : null,
            'employee_role_id' => $request->role_id,
            'department_id' => $request->department_id,
            'password' => bcrypt($request->password),
            'vendor_id' => Helpers::get_vendor_id(),
            'store_id' => Helpers::get_store_id(),
            'image' => Helpers::upload('vendor/', 'png', $request->file('image')),
            'documents' =>  json_encode($documents),

            // addresses
            'residential_address' => isset($residential_address) ? json_encode($residential_address) :  null,
            'permanent_address' => isset($permanent_address) ? json_encode($permanent_address) : null,

            //professional information
            'designation' => $request->designation,
            'experience_yrs' => $request->experience,
            'source' => $request->source,
            'qualification' => $request->highest_qualification,
            'additional_information' => $request->additional_information,
            // 'current_salary' => $request->current_salary, //old salary
            'base_salary' => $request->salary_type == 'Task-Wise' ? null : $request->base_salary,
            'salary_type' => $request->salary_type,
            'main_department' => $request->main_department,
            'offer_letter' => $offer_letter ?? null, // file
            'tentative_joining_date' => $request->tentative_joining_date,

            //emergency contact details
            'emergency_contact_details' => isset($emergency_contact_details) ? json_encode($emergency_contact_details) :  null,
            'emergency_phone' => $emergency_phone ?? null,

            //educational information
            'experience' => $experience ? json_encode($experience) : null,
            'education' => $education ? json_encode($education) :  null,

            //documentation
            'documentation' => $documentation ? json_encode($documentation) : null, // file

            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->createEmployeeLedger($employeeId);
        Toastr::success('Staff added successfully!');
        return redirect()->back();
    }

    private function createEmployeeLedger($employee_id)
    {
        $employee = VendorEmployee::with('department')->where('id', $employee_id)->first();
        $storeId = Helpers::get_store_id();
        $fullName = trim($employee->f_name . ' ' . $employee->l_name);

        // ----------------------------
        // Ensure Salary ledger exists
        // ----------------------------
        $salaryLedger = StoreAccount::where('store_id', $storeId)
            ->where('name', 'Salary')
            ->first();

        if (!$salaryLedger) {
            // If Salary ledger does not exist, create it under Expenses
            $expenseTypeId = LedgerAccountType::where('name', 'Expenses')->first()->id ?? 1;
            $salaryLedger = StoreAccount::create([
                'store_id' => $storeId,
                'ledger_account_type_id' => $expenseTypeId,
                'parent_id' => null, // top-level
                'code' => _accountCode($expenseTypeId, null),
                'name' => 'Salary',
                'description' => 'Salary Ledger',
                'level' => 1,
                'entity_type' => 'store'
            ]);
        }

        // ----------------------------
        // Ensure Department ledger under Salary exists
        // ----------------------------
        $deptAccount = StoreAccount::find($employee->department->ledger_account_id);
        if (!$deptAccount) {
            $deptAccount = StoreAccount::create([
                'store_id' => $storeId,
                'ledger_account_type_id' => $salaryLedger->ledger_account_type_id,
                'parent_id' => $salaryLedger->id,
                'code' => _accountCode($salaryLedger->ledger_account_type_id, $salaryLedger->id),
                'name' => $employee->department->title,
                'description' => "Department Ledger for {$employee->department->title}",
                'level' => $salaryLedger->level + 1,
                'entity_type' => 'store'
            ]);

            // Save ledger id to department
            $employee->department->update(['ledger_account_id' => $deptAccount->id]);
        }

        // ----------------------------
        // Create employee ledger
        // ----------------------------
        $ledger = StoreAccount::create([
            'store_id' => $storeId,
            'ledger_account_type_id' => $deptAccount->ledger_account_type_id,
            'parent_id' => $deptAccount->id,
            'code' => _accountCode($deptAccount->ledger_account_type_id, $deptAccount->id),
            'name' => $fullName,
            'description' => "Salary Ledger for {$fullName}",
            'level' => $deptAccount->level + 1,
            'entity_type' => 'customer'
        ]);

        // Save ledger id to employee
        $employee->update(['ledger_account_id' => $ledger->id]);
    }



    private function updateEmployeeLedger($employee_id, $oldDeptId)
    {
        $employee = VendorEmployee::with('department')->where('id', $employee_id)->first();
        $storeId = Helpers::get_store_id();
        $fullName = trim($employee->f_name . ' ' . $employee->l_name);

        // ----------------------------
        // Ensure Salary ledger exists
        // ----------------------------
        $salaryLedger = StoreAccount::where('store_id', $storeId)
            ->where('name', 'Salary')
            ->first();

        if (!$salaryLedger) {
            $expenseTypeId = LedgerAccountType::where('name', 'Expenses')->first()->id ?? 1;
            $salaryLedger = StoreAccount::create([
                'store_id' => $storeId,
                'ledger_account_type_id' => $expenseTypeId,
                'parent_id' => null,
                'code' => _accountCode($expenseTypeId, null),
                'name' => 'Salary',
                'description' => 'Salary Ledger',
                'level' => 1,
                'entity_type' => 'store'
            ]);
        }

        // ----------------------------
        // Ensure Department ledger under Salary exists
        // ----------------------------
        $deptAccount = StoreAccount::find($employee->department->ledger_account_id);
        if (!$deptAccount) {
            $deptAccount = StoreAccount::create([
                'store_id' => $storeId,
                'ledger_account_type_id' => $salaryLedger->ledger_account_type_id,
                'parent_id' => $salaryLedger->id,
                'code' => _accountCode($salaryLedger->ledger_account_type_id, $salaryLedger->id),
                'name' => $employee->department->title,
                'description' => "Department Ledger for {$employee->department->title}",
                'level' => $salaryLedger->level + 1,
                'entity_type' => 'store'
            ]);

            $employee->department->update(['ledger_account_id' => $deptAccount->id]);
        }

        // ----------------------------
        // Get or create Employee ledger
        // ----------------------------
        $ledger = StoreAccount::find($employee->ledger_account_id);
        if (!$ledger) {
            $ledger = StoreAccount::create([
                'store_id' => $storeId,
                'ledger_account_type_id' => $deptAccount->ledger_account_type_id,
                'parent_id' => $deptAccount->id,
                'code' => _accountCode($deptAccount->ledger_account_type_id, $deptAccount->id),
                'name' => $fullName,
                'description' => "Salary Ledger for {$fullName}",
                'level' => $deptAccount->level + 1,
                'entity_type' => 'employee'
            ]);

            $employee->update(['ledger_account_id' => $ledger->id]);
        }

        // ----------------------------
        // Move ledger if department changed
        // ----------------------------
        if ($oldDeptId != $employee->department_id) {
            $ledger->update([
                'parent_id' => $deptAccount->id,
                'ledger_account_type_id' => $deptAccount->ledger_account_type_id,
                'level' => $deptAccount->level + 1,
                'entity_type' => 'employee',
                'description' => "Salary Ledger for {$fullName}",
            ]);
        }

        // ----------------------------
        // Always update ledger name
        // ----------------------------
        $ledger->update(['name' => $fullName]);
    }


    private function deleteEmployeeLedger($employee_id)
    {
        $employee = VendorEmployee::find($employee_id);
        $ledger = StoreAccount::find($employee->ledger_account_id);
        if ($ledger) {
            $ledger->delete();
        }
    }
    function view(Request $request, $id)
    {
        $emp = VendorEmployee::with('role', 'department', 'storeShift', 'comments', 'branch')->where('store_id', Helpers::get_store_id())->where('id', $id)->first();
        if (!$emp) {
            Toastr::error('Not found!');
            return redirect()->back();
        }

        // chart js
        $months = [];
        $now = Carbon::now();

        for ($m = 1; $m <= $now->month; $m++) {
            $months[] = Carbon::create()->month($m)->format('F'); // Jan, Feb, ...
        }
        $chart_data['months'] = $months;

        $currentYear = Carbon::now()->year;
        $monthlyLeads = AcceptedServiceRequest::join('service_requests', 'service_requests.id', '=', 'accepted_service_requests.service_request_id')
            ->where('accepted_service_requests.vendor_id', Helpers::get_store_id())
            ->where('accepted_service_requests.assigned_type', 'staff')
            ->where('accepted_service_requests.assigned_to', $id)
            ->where('accepted_service_requests.current_status', 'Completed')
            ->whereYear('accepted_service_requests.assigned_at', $currentYear)
            ->selectRaw('MONTH(accepted_service_requests.assigned_at) as month, COUNT(*) as count')
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('count', 'month');
        $monthlyTasks = StoreTask::where('store_id', Helpers::get_store_id())->where('status', 'Completed')->whereYear('created_at', $currentYear)->where('employee_id', $id)->selectRaw('MONTH(created_at) as month, COUNT(*) as count')
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('count', 'month');
        $monthlyProjects = Project::where('vendor_id', Helpers::get_store_id())->where('team_leader', $id)->where('progress_status', 'Completed')->whereYear('created_at', $currentYear)->selectRaw('MONTH(created_at) as month, COUNT(*) as count')->groupBy('month')
            ->orderBy('month')
            ->pluck('count', 'month');
        // prx( $monthlyProjects);

        $leadsData = [];
        $tasksData = [];
        $projectsData = [];

        for ($m = 1; $m <= Carbon::now()->month; $m++) {
            $months[] = Carbon::create()->month($m)->format('F');
            $leadsData[] = $monthlyLeads->get($m, 0);
            $tasksData[] = $monthlyTasks->get($m, 0);
            $projectsData[] = $monthlyProjects->get($m, 0);
        }

        $chart_data['leads'] = $leadsData;     // count per month
        $chart_data['projects'] =  $tasksData;
        $chart_data['tasks'] = $projectsData;
        // prx($chart_data);

        $tasks = StoreTask::where('store_id', Helpers::get_store_id())->where('employee_id', $id)->take(3)->get();
        $projects = Project::where('vendor_id', Helpers::get_store_id())->where('team_leader', $id)->take(3)->get();
        $leads = AcceptedServiceRequest::join('service_requests', 'service_requests.id', 'accepted_service_requests.service_request_id')
            ->join("items", 'items.id', 'service_requests.item_id')
            ->where('accepted_service_requests.vendor_id', Helpers::get_store_id())->where("accepted_service_requests.assigned_type", 'staff')->where('accepted_service_requests.assigned_to', $id)->orderBy('accepted_service_requests.id', 'desc')
            ->select('accepted_service_requests.current_status', 'accepted_service_requests.service_request_id', 'items.name', 'accepted_service_requests.assigned_at')
            ->distinct('service_requests.id')->take(3)->get();
        // prx( $leads);

        $advance = AdvanceRequest::where('employee_id', $id)
            ->where('status', 'approved')
            ->where('approved_amount', '>', 0)
            ->first();

        $data['advance_payment_deductions'] = $advance ? $advance->approved_amount : 0;
        $data['alotted_assets'] = AssetAlotment::with('inventoryItem')->where('employee_id', $id)->get();
        // prx($data['alotted_assets']);

        return view('vendor-views.employee.view', compact('chart_data', 'emp', 'tasks', 'projects', 'leads', 'data'));
    }
    public function assignShift(Request $request, $employeeId)
    {
        $request->validate([
            'store_shift_id' => 'required|exists:store_shifts,id',
        ]);

        $employee = VendorEmployee::findOrFail($employeeId);
        $employee->store_shift_id = $request->store_shift_id;
        $employee->save();

        return back()->with('success', 'Shift assigned successfully.');
    }

    function view_id_card(Request $request, $id)
    {
        $emp = VendorEmployee::where('store_id', Helpers::get_store_id())->where('id', $id)->first();
        if (!$emp) {
            Toastr::error('Not found!');
            return redirect()->back();
        }
        return view('vendor-views.employee.id_card', compact('emp'));
    }
    function list(Request $request)
    {
        $key = explode(' ', $request['search']);
        $status  = $request->status ?? '';
        $today = now()->toDateString();

        $em = VendorEmployee::where('store_id', Helpers::get_store_id())
            ->with(['role'])

            ->when(isset($key), function ($query) use ($key) {
                $query->where(function ($q) use ($key) {
                    foreach ($key as $value) {
                        $q->orWhere('f_name', 'like', "%{$value}%")
                            ->orWhere('l_name', 'like', "%{$value}%")
                            ->orWhere('phone', 'like', "%{$value}%")
                            ->orWhere('email', 'like', "%{$value}%");
                    }
                });
            })
            ->when($status == 'on_duty', function ($query) use ($today) {
                $query->whereIn('id', function ($sub) use ($today) {
                    $sub->select('emp_id')
                        ->from('employee_time_cards')
                        ->whereDate('in_time', $today);
                });
            })
           ->when($status === 'off_duty', function ($query) use ($today) {
    $query->whereNotIn('id', function ($sub) use ($today) {
        $sub->select('emp_id')
            ->from('employee_time_cards')
            ->whereDate('in_time', $today);
    });
})

            ->latest()
            ->paginate(config('default_pagination'));

        return view('vendor-views.employee.list', compact('em'));
    }

    public function edit($id)
    {
        $e = VendorEmployee::where('store_id', Helpers::get_store_id())->where(['id' => $id])->first();
        $rls = EmployeeRole::where('store_id', Helpers::get_store_id())->get();
        $departments = Department::where('vendor_id', Helpers::get_store_id())->where('status', '1')->get();
        if (auth('vendor_employee')->id()  != $e['id']) {
            return view('vendor-views.employee.edit', compact('rls', 'e', 'departments'));
        }
        Toastr::warning(translate('messages.access_denied'));
        return back();
    }

    public function update(Request $request, $id)
    {
        // prx($request->all());
        $request->validate([
            'f_name' => 'required',
            // 'l_name' => 'nullable|max:100',
            'role_id' => 'required',
            // 'qualification' => 'required',
            'occupation.*' => 'required',
            'school_name.*' => 'required',
            // 'image' => 'required',
            'email' => 'required|unique:vendor_employees,email,' . $id,
            'phone' => 'required|regex:/^([0-9\s\-\+\(\)]*)$/|max:20|unique:vendor_employees,phone,' . $id,
            'password' => ['nullable', Password::min(8)->mixedCase()->letters()->numbers()->symbols()],
        ], [
            'f_name.required' => translate('messages.first_name_is_required'),
        ]);
        $e = VendorEmployee::where('store_id', Helpers::get_store_id())->find($id);
        $oldDeptId = $e->department_id;

        if ($request['password'] == null) {
            $pass = $e['password'];
        } else {
            if (strlen($request['password']) < 7) {
                Toastr::warning(translate('messages.password_length_warning', ['length' => '8']));
                return back();
            }
            $pass = bcrypt($request['password']);
            $e->remember_token = null;
        }
        $updated_docs =  json_decode($e->documents);
        if (!empty($request->file('documents'))) {
            foreach ($request->documents as $file) {
                $extension = $file->getClientOriginalExtension();
                $doc_name = Helpers::upload('vendor/documents/', $extension, $file);
                $documents[] = $doc_name;
            }
            if (json_decode($e->documents)) {
                $updated_docs =  array_merge(json_decode($e->documents), $documents);
            } else {
                $updated_docs =  $documents;
            }
        }

        if ($request->ra_address1) {
            $residential_address = [
                'ra_email' =>  $request->ra_email ??  null,
                'ra_phone' =>  $request->ra_phone,
                'ra_address1' =>  $request->ra_address1,
                'ra_address2' =>  $request->ra_address2,
                'ra_pincode' =>  $request->ra_pincode,
                'ra_city' =>  $request->ra_city,
                'ra_state' =>  $request->ra_state
            ];
        }
        if ($request->pa_address1) {
            $permanent_address = [
                'pa_email' =>  $request->pa_email ??  null,
                'pa_phone' =>  $request->pa_phone,
                'pa_address1' =>  $request->pa_address1,
                'pa_address2' =>  $request->pa_address2,
                'pa_pincode' =>  $request->pa_pincode,
                'pa_city' =>  $request->pa_city,
                'pa_state' =>  $request->pa_state
            ];
        }
        $education = [];
        if ($request->has('school_name') && $request->school_name[0]) {
            foreach ($request->school_name as $key => $value) {
                $education[] = [
                    'school_name' => $request->school_name[$key],
                    'degree_diploma' => $request->degree_diploma[$key],
                    'field_of_study' => $request->field_of_study[$key],
                    'start_month' => $request->start_month[$key],
                    'end_month' => $request->end_month[$key],
                    'additional_notes' => $request->additional_notes[$key],
                ];
            }
        }
        $experience = [];
        if ($request->has('occupation') && $request->occupation[0]) {
            foreach ($request->occupation as $key => $value) {
                if ($request->hasFile("experience_letter.$key")) {
                    // Remove old file if exists
                    if (!empty($request->existing_exp_letter[$key])) {
                        if (Storage::disk('public')->exists('vendor/documents/' . $request->existing_exp_letter[$key])) {
                            Storage::disk('public')->delete('vendor/documents/' . $request->existing_exp_letter[$key]);
                        }
                    }
                    $file = $request->file("experience_letter.$key");
                    $extension = $file->getClientOriginalExtension();
                    $experience_letter = Helpers::upload('vendor/documents/', $extension, $file);
                }
                $experience[] = [
                    'occupation' => $request->occupation[$key],
                    'company_name' => $request->company_name[$key],
                    'summary' => $request->summary[$key],
                    'exp_start_date' => $request->exp_start_date[$key] ?? null,
                    'currently_working' => $request->currently_working[$key] ?? null,
                    'exp_end_date' => $request->exp_end_date[$key] ?? null,
                    'exp_letter' => $experience_letter ?? ($request->existing_exp_letter[$key] ?? null),
                ];
            }
        }
        // prx($request->all()); 
        $documentation = [];
        if ($request->has('doc_name')) {
            foreach ($request->doc_name as $key => $value) {
                if ($request->doc_number[$key] || $request->doc_name[$key]) {
                    if ($request->hasFile("doc_file.$key")) {
                        // Remove old file if exists
                        if (!empty($request->doc_file[$key])) {
                            if (Storage::disk('public')->exists('vendor/documents/' . $request->doc_file_old[$key])) {
                                Storage::disk('public')->delete('vendor/documents/' . $request->doc_file_old[$key]);
                            }
                        }
                        $file = $request->file("doc_file.$key");
                        $extension = $file->getClientOriginalExtension();
                        $doc_file = Helpers::upload('vendor/documents/', $extension, $file);
                    } else {
                        $doc_file = $request->doc_file_old[$key];
                    }
                    $documentation[] = [
                        'doc_name' => $request->doc_name[$key] ?? null,
                        'doc_number' => $request->doc_number[$key],
                        'doc_file' => $doc_file ??  null,
                    ];
                    $doc_file = null; // resetting
                }
            }
        }


        if ($request->file('offer_letter')) {
            $file = $request->file('offer_letter');
            $extension = $file->getClientOriginalExtension();
            $offer_letter = Helpers::upload('vendor/documents/', $extension, $file);
        }

        if ($request->file('id_document')) {
            $file = $request->file('id_document');
            $extension = $file->getClientOriginalExtension();
            $id_document = Helpers::upload('vendor/documents/', $extension, $file);
        }

        if ($request->has('image')) {
            $e['image'] = Helpers::update('vendor/', $e->image, 'png', $request->file('image'));
        }
        $e->f_name = $request->f_name;
        $e->l_name = $request->l_name;
        $e->phone = $request->phone;
        $e->qualification = $request->qualification;
        $e->email = $request->email;
        $e->qualification = $request->qualification;
        $e->skills = $request->skills ?  implode(',', $request->skills) : '';
        $e->employee_role_id = $request->role_id;
        $e->vendor_id = Helpers::get_vendor_id();
        $e->store_id = Helpers::get_store_id();
        $e->password = $pass;
        $e->image = $e['image'];
        $e->documents =  json_encode($updated_docs);
        $e->store_shift_id = $request->shift;
        $e->branch_id = $request->branch_id;
        $e->department_id = $request->department_id;

        $e->residential_address = isset($residential_address) ? json_encode($residential_address) :  $e->residential_address;
        $e->permanent_address = isset($permanent_address) ? json_encode($permanent_address) :  $e->permanent_address;

        $e->designation = $request->designation;
        $e->experience_yrs = $request->experience;
        $e->source = $request->source;
        $e->qualification = $request->highest_qualification;
        $e->additional_information = $request->additional_information;
        $e->base_salary = $request->salary_type == 'Task-Wise' ? null : $request->base_salary;
        $e->salary_type = $request->salary_type;
        $e->main_department = $request->main_department;
        $e->offer_letter = $offer_letter ?? $e->offer_letter;
        $e->tentative_joining_date = $request->tentative_joining_date;

        $e->experience = $experience ? json_encode($experience) : $e->experience;
        $e->education = $education ? json_encode($education) : $e->education;

        //other information
        $e->documentation = $documentation ? json_encode($documentation) : null;

        $e->updated_at = now();
        $e->save();

        $this->updateEmployeeLedger($e->id, $oldDeptId);

        Toastr::success('Staff updated successfully!');
        return redirect()->route('vendor.staff.list');
    }

    public function distroy($id)
    {
        $role = VendorEmployee::where('store_id', Helpers::get_store_id())->where(['id' => $id])->delete();
        Toastr::success('Staff Deleted Successfully');
        return back();
    }

    // public function search(Request $request){
    //     $key = explode(' ', $request['search']);
    //     $employees=VendorEmployee::where('store_id', Helpers::get_store_id())->
    //     where(function ($q) use ($key) {
    //         foreach ($key as $value) {
    //             $q->orWhere('f_name', 'like', "%{$value}%");
    //             $q->orWhere('l_name', 'like', "%{$value}%");
    //             $q->orWhere('phone', 'like', "%{$value}%");
    //             $q->orWhere('email', 'like', "%{$value}%");
    //         }
    //     })->limit(50)->get();
    //     return response()->json([
    //         'view'=>view('vendor-views.employee.partials._table',compact('employees'))->render(),
    //         'count'=>$employees->count()
    //     ]);
    // }

    public function view_terms_and_conditions(Request $request)
    {
        $terms_and_conditions = '';
        $terms = DB::table('vendor_terms_conditions')->where('type', 'for_staff')->where('vendor_id', Helpers::get_store_id())->first();
        if ($terms) {
            $terms_and_conditions = $terms->terms_n_conditons;
        }
        return view('vendor-views.terms-and-conditions', compact('terms_and_conditions'));
    }
    public function list_export(Request $request)
    {

        $key = explode(' ', $request['search']);
        $em = VendorEmployee::where('store_id', Helpers::get_store_id())->with(['role'])

            ->when(isset($key), function ($query) use ($key) {
                $query->where(function ($q) use ($key) {
                    foreach ($key as $value) {
                        $q->orWhere('f_name', 'like', "%{$value}%");
                        $q->orWhere('l_name', 'like', "%{$value}%");
                        $q->orWhere('phone', 'like', "%{$value}%");
                        $q->orWhere('email', 'like', "%{$value}%");
                    }
                });
            })
            ->latest()->get();
        $data = [
            'employees' => $em,
            'search' => $request->search ?? null,
        ];

        if ($request->type == 'excel') {
            return Excel::download(new StoreEmployeeListExport($data), 'Employees.xlsx');
        } else if ($request->type == 'csv') {
            return Excel::download(new StoreEmployeeListExport($data), 'Employees.csv');
        }


        // if($request->type == 'excel'){
        //     return (new FastExcel($em))->download('Employee.xlsx');
        // }elseif($request->type == 'csv'){
        //     return (new FastExcel($em))->download('Employee.csv');
        // }
    }
}
