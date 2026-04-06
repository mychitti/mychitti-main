<?php

namespace App\Http\Controllers\Admin\Employee;

use App\Contracts\Repositories\CustomRoleRepositoryInterface;
use App\Contracts\Repositories\EmployeeRepositoryInterface;
use App\Contracts\Repositories\ZoneRepositoryInterface;
use App\Enums\ExportFileNames\Admin\Employee;
use App\Enums\ViewPaths\Admin\Employee as EmployeeViewPath;
use App\Exports\EmployeeListExport;
use App\Http\Controllers\BaseController;
use App\Http\Requests\Admin\EmployeeAddRequest;
use App\Http\Requests\Admin\EmployeeUpdateRequest; 
use App\Models\AcceptedServiceRequest;
use App\Models\Admin;
use App\Models\VendorEmployee;
use App\Models\AdvanceRequest; 
use App\Models\Department;
use App\Models\AssetAlotment;
use App\Models\Project;
use App\Models\StoreTask;
use App\Services\EmployeeService; 
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request; 
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use App\Models\Attendance;
use App\Models\EmployeeTimeCard;
use Carbon\Carbon;

class EmployeeController extends BaseController
{
    public function __construct(
        protected EmployeeRepositoryInterface $employeeRepo,
        protected CustomRoleRepositoryInterface $roleRepo,
        protected EmployeeService $employeeService,
        protected ZoneRepositoryInterface $zoneRepo,
    )
    {
    }
  function view_id_card(Request $request, $id)
    {
        $emp = Admin::where('id', $id)->first();
        if (!$emp) {
            Toastr::error('Not found!');
            return redirect()->back();
        }
        return view('admin-views.employee.id_card', compact('emp'));
    }
    public function index(?Request $request): View|Collection|LengthAwarePaginator|null
    {
        return $this->getListView($request);
    }

    public function getAddView(): View
    {
        $roles = $this->roleRepo->getList();
        $zones = $this->zoneRepo->getList();
        $departments = Department::where('vendor_id', 0)->orderBy('title')->get();
        return view(EmployeeViewPath::ADD[VIEW], compact('roles','zones','departments'));
    }
    private function getListView(Request $request): View
    {
        $zoneId = $request->query('zone_id', 'all');
        $employees = $this->employeeRepo->getZoneWiseListWhere(searchValue: $request['search'],
        relations:['role'],
        zoneId: $zoneId,
        dataLimit: config('default_pagination'));
        return view(EmployeeViewPath::INDEX[VIEW], compact('employees'));
    }
   function view(Request $request, $id)
    {
        $emp = Admin::with('role', 'department', 'storeShift', 'comments', 'branch')->where('role_id', '!=', 0)->where('id', $id)->first();
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
            ->where('accepted_service_requests.vendor_id', 0)
            ->where('accepted_service_requests.assigned_type', 'staff')
            ->where('accepted_service_requests.assigned_to', $id)
            ->where('accepted_service_requests.current_status', 'Completed')
            ->whereYear('accepted_service_requests.assigned_at', $currentYear)
            ->selectRaw('MONTH(accepted_service_requests.assigned_at) as month, COUNT(*) as count')
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('count', 'month');
        $monthlyTasks = StoreTask::where('store_id', 0)->where('status', 'Completed')->whereYear('created_at', $currentYear)->where('employee_id', $id)->selectRaw('MONTH(created_at) as month, COUNT(*) as count')
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('count', 'month');
        $monthlyProjects = Project::where('vendor_id', 0)->where('team_leader', $id)->where('progress_status', 'Completed')->whereYear('created_at', $currentYear)->selectRaw('MONTH(created_at) as month, COUNT(*) as count')->groupBy('month')
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

        $tasks = StoreTask::where('store_id', 0)->where('employee_id', $id)->take(3)->get();
        $projects = Project::where('vendor_id', 0)->where('team_leader', $id)->take(3)->get();
        $leads = AcceptedServiceRequest::join('service_requests', 'service_requests.id', 'accepted_service_requests.service_request_id')
            ->join("items", 'items.id', 'service_requests.item_id')
            ->where('accepted_service_requests.vendor_id', 0)->where("accepted_service_requests.assigned_type", 'staff')->where('accepted_service_requests.assigned_to', $id)->orderBy('accepted_service_requests.id', 'desc')
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

        return view('admin-views.employee.view', compact('chart_data', 'emp', 'tasks', 'projects', 'leads', 'data'));
    }
    public function add(EmployeeAddRequest $request): RedirectResponse
    {
        $this->employeeRepo->add(data: $this->employeeService->getAddData(request: $request));

        Toastr::success(translate('messages.employee_added_successfully'));
        return redirect()->route('admin.users.employee.list');
    }

    public function getUpdateView(string|int $id): RedirectResponse|View
    {
        $employee = $this->employeeRepo->getFirstWhereExceptAdmin(params: ['id' => $id]);
        $roles = $this->roleRepo->getList();
        $data = $this->employeeService->adminCheck(employee: $employee);
        $zones = $this->zoneRepo->getList();
        $departments = Department::where('vendor_id' ,0)->orderBy('title')->get();

        if (array_key_exists('flag', $data) && $data['flag'] == 'unauthorized') {
            return view(EmployeeViewPath::UPDATE[VIEW], compact('roles', 'employee','zones','departments'));
        }

        Toastr::warning(translate('messages.access_denied'));
        return back();
    }

    public function update(EmployeeUpdateRequest $request, $id): RedirectResponse|View
    {
        $employee = $this->employeeRepo->getFirstWhereExceptAdmin(params: ['id' => $id]);

        $this->employeeRepo->update(id: $id ,data: $this->employeeService->getUpdateData(request: $request,employee: $employee));

        Toastr::success(translate('messages.employee_updated_successfully'));
        return back();
    }

    public function delete($id): RedirectResponse|View
    {
        $this->employeeRepo->delete(id: $id);
        Toastr::success(translate('messages.employee_deleted_successfully'));
        return back();
    }

    public function search(Request $request): JsonResponse
    {
        $employees=$this->employeeRepo->getSearchList($request);
        return response()->json([
            'view'=>view(EmployeeViewPath::SEARCH[VIEW],compact('employees'))->render(),
            'count'=>$employees->count()
        ]);
    }

    public function getSearchList(Request $request): JsonResponse
    {

        $employees = $this->employeeRepo->getSearchList(request: $request);

        return response()->json([
            'view'=>view(EmployeeViewPath::SEARCH[VIEW],compact('employees'))->render(),
            'count'=>$employees->count()
        ]);
    }

    public function resign(Request $request, $id): RedirectResponse
    {
        $request->validate([
            'reason' => 'required|string',
        ]);

        DB::table('employee_resignations')->insert([
            'employee_id' => $id,
            'store_id' => 0,
            'reason' => $request->reason,
            'created_at' => now(),
        ]);

        Toastr::success('Resignation Submitted Successfully');
        return back();
    }

    public function terminate(Request $request, $id): RedirectResponse
    {
        $emp = Admin::where('role_id', '!=', 1)->where('id', $id)->first();
        if (!$emp) {
            Toastr::error('Employee not found!');
            return back();
        }
        $emp->terminate = 1;
        $emp->status = 0; 
        $emp->save();

        Toastr::success('Employee Terminated Successfully');
        return back();
    }

    public function resignation_action(Request $request, $id, $action): RedirectResponse
    {
        $rr = DB::table('employee_resignations')->where('id', $id)->first();

        if ($rr) {
            DB::table('employee_resignations')
                ->where('id', $id)
                ->update(['status' => $action, 'updated_at' => now()]);

            if ($action == 'approved') {
                if ($rr->store_id != 0) {
                    // vendor/store employee
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
                } else {
                    // admin employee
                    $emp = Admin::where('role_id', '!=', 1)->where('id', $rr->employee_id)->first();
                    if ($emp) {
                        $emp->status = 0;
                        $emp->save();
                    }
                }
            }

            Toastr::success(ucfirst($action) . ' successfully');
            return back();
        }

        Toastr::error('Resignation request not found');
        return back();
    }

    public function clock_in(): JsonResponse
    {
        $emp_id = auth('admin')->id();

        $obj = new EmployeeTimeCard;
        $obj->emp_id = $emp_id;
        $obj->vendor_id = 0;
        $obj->in_time = date('Y-m-d H:i:s');
        $obj->date = date('Y-m-d');
        $obj->created_at = date('Y-m-d H:i:s');

        $att = Attendance::where('employee_id', $emp_id)
            ->where('employee_type', 'staff')
            ->where('vendor_id', 0)
            ->where('date', date('Y-m-d'))
            ->first();

        if (!$att) {
            $att = new Attendance;
            $att->employee_id = $emp_id;
            $att->vendor_id = 0;
            $att->employee_type = 'staff';
            $att->date = date('Y-m-d');
            $att->day = date('d');
            $att->month = date('m');
            $att->year = date('Y');
        }
        $att->label = 'P';
        $att->created_at = date('Y-m-d H:i:s');
        $att->save();

        if ($obj->save()) {
            return response()->json(['status' => true]);
        } else {
            return response()->json(['status' => false]);
        }
    }

    public function clock_out(): JsonResponse
    {
        $emp_id = auth('admin')->id();

        $today_attendance = EmployeeTimeCard::where('emp_id', $emp_id)
            ->where('vendor_id', 0)
            ->orderBy('id', 'desc')
            ->limit(1)
            ->first();

        if (!$today_attendance) {
            return response()->json(['status' => false]);
        }

        $obj = EmployeeTimeCard::find($today_attendance->id);
        $obj->out_time = date('Y-m-d H:i:s');

        if ($obj->update()) {
            return response()->json(['status' => true]);
        } else {
            return response()->json(['status' => false]);
        }
    }

    public function timecards(Request $request, $id): View|RedirectResponse
    {
        $emp = Admin::where('role_id', '!=', 1)->where('id', $id)->first();
        if (!$emp) {
            Toastr::error('Employee not found!');
            return redirect()->back();
        }

        $currentmonth = $request->get('month', date('Y-m'));

        $timecards = EmployeeTimeCard::where('emp_id', $id)
            ->where('vendor_id', 0)
            ->where('date', '>=', $currentmonth . '-01')
            ->where('date', '<=', $currentmonth . '-31')
            ->whereNotNull('out_time')
            ->whereNotNull('in_time')
            ->orderBy('date', 'desc')
            ->get();

        return view('admin-views.employee.timecards', compact('timecards', 'currentmonth', 'emp'));
    }

    public function exportList(Request $request): BinaryFileResponse
    {
        $employees = $this->employeeRepo->getExportList(request: $request);

        $data=[
            'employees' =>$employees,
            'search' =>$request['search'] ?? null,
        ];

        if($request['type'] == 'csv'){
            return Excel::download(new EmployeeListExport($data), Employee::EXPORT_CSV);
        }
        return Excel::download(new EmployeeListExport($data), Employee::EXPORT_XLSX);
    }
}
