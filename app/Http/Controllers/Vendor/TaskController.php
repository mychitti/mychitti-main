<?php

namespace App\Http\Controllers\Vendor;

use Carbon\Carbon;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use App\Models\Lead;
use App\Models\ManualInvoice;
use App\Models\Quotation;
use App\Models\EmployeeRole;
use App\Models\VendorEmployee;
use Brian2694\Toastr\Facades\Toastr;
use App\CentralLogics\Helpers;
use App\Exports\TaskExport;
use App\Models\AccountDropdownOption;
use App\Models\Department;
use App\Models\Form;
use App\Models\FormSubmission;
use App\Models\InventoryItem;
use App\Models\JobCard;
use App\Models\Project;
use App\Models\ReceivableReceipt;
use App\Models\ServiceReport;
use App\Models\Staff;
use App\Models\Store;
use App\Models\StoreConfig;
use App\Models\StoreCustomer;
use App\Models\StoreEnabledModule;
use App\Models\StoreTask;
use App\Models\TaskComment;
use App\Models\TaskSalaryCategory;
use App\Models\TaskStatus;
use App\Models\TempEmployee;
use App\Models\Vendor;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class TaskController extends Controller
{
    private function logTaskTimeCardEvent(StoreTask $task, string $event): void
    {
        DB::table('task_time_card_events')->insert([
            'task_id' => $task->id,
            'store_id' => $task->store_id,
            'event_type' => $event,
            'event_time' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function upsertTaskTimeCard(StoreTask $task, string $event): void
    {
        $now = now();
        $existing = DB::table('task_time_cards')->where('task_id', $task->id)->first();

        $payload = [
            'store_id' => $task->store_id,
            'updated_at' => $now,
        ];

        if (!$existing) {
            $payload['task_id'] = $task->id;
            $payload['created_at'] = $now;
        }

        if ($event === 'start') {
            if (!$existing || !$existing->start_time) {
                $payload['start_time'] = $now;
            }
        } elseif ($event === 'pause') {
            $payload['pause_time'] = $now;
        } elseif ($event === 'resume') {
            $payload['resume_time'] = $now;
        } elseif ($event === 'end') {
            $payload['end_time'] = $now;
        }

        if ($existing) {
            DB::table('task_time_cards')->where('task_id', $task->id)->update($payload);
        } else {
            DB::table('task_time_cards')->insert($payload);
        }

        $this->logTaskTimeCardEvent($task, $event);
    }

    public function  getTargetTime($task)
    {
        $created_at = $task->created_at; // Example created_at from DB
        $estimation_count = $task->time_count;            // For example: 4.56
        $estimation_unit = $task->time_unit;           // Can be: hour, day, week, month

        // Convert to timestamp
        $created_at_time = strtotime($created_at);

        // Convert estimation to seconds
        switch ($estimation_unit) {
            case 'hour':
                $estimation_seconds = $estimation_count * 3600;
                break;
            case 'day':
                $estimation_seconds = $estimation_count * 86400;
                break;
            case 'week':
                $estimation_seconds = $estimation_count * 7 * 86400;
                break;
            case 'month':
                $estimation_seconds = $estimation_count * 30 * 86400; // Approximation
                break;
            default:
                $estimation_seconds = 0;
        }
        return $created_at_time + $estimation_seconds;
    }
    public function detail(Request $request, $id)
    {
        $task = StoreTask::with(['user', 'formSubmission', 'formSubmission.form', 'children'])->where('id', $id)->where('store_id', Helpers::get_store_id())->first();
        if (!$task) {
            Toastr::success('Not Found');
            return back();
        }
        // prx( $task);
        $tasks = StoreTask::with('allChildren')
            ->where('store_id', Helpers::get_store_id())
            ->where('parent_id', $id)

            ->whereNotNull('parent_id')
            ->get()
            ->map(function ($task) {
                return $this->formatTaskForJs($task);
            });


        $data['target_time'] = $this->getTargetTime($task);

        $store_id = $storeId = Helpers::get_store_id();

        if ($task->employee_id === 0) {
            $task['emp_name'] = 'Self';
            $task['emp_phone'] = Helpers::get_store_data()->phone;
            $task['emp_role'] = '-';
            $task['emp_image'] =  Helpers::get_store_data()->logo;
        } else if ($task->employee_id !== 0 && $task->employee_id !== null) {
            $empdet = VendorEmployee::with('role')->find($task->employee_id);
            $task['emp_name'] = $empdet ?  $empdet->f_name . ' ' . $empdet->l_name : 'Deleted';
            $task['emp_phone'] =  $empdet ? $empdet->phone : '';
            $task['emp_role'] = $empdet ? $empdet->role?->name : '';
            $task['emp_image'] =  $empdet ? $empdet->image : '';
        } else if ($task->employee_id === null && $task->offered_to != null) {
            $empdet = VendorEmployee::with('role')->find($task->offered_to);
            $task['emp_name'] = $empdet ?  $empdet->f_name . ' ' . $empdet->l_name : 'Deleted';
            $task['emp_phone'] =  $empdet ? $empdet->phone : '';
            $task['emp_role'] = $empdet ? $empdet->role?->name : '';
            $task['emp_image'] =  $empdet ? $empdet->image : '';
        } else {
            $empdet = VendorEmployee::with('role')->find($task->offered_to);
            $task['emp_name'] = '';
            $task['emp_phone'] = '';
            $task['emp_role'] = '';
            $task['emp_image'] =  Helpers::get_store_data()->logo;
        }

        $staff = VendorEmployee::with('role')->where('store_id', $store_id)->whereNot('terminate', 1)->where('status', 1)->get();

        $task_statuses = TaskStatus::where('task_id', $task->id)->get();
        $task_comments = TaskComment::where('store_id', $store_id)->where('task_id', $task->id)->get();
        $statuses = Helpers::get_store_data()->task_statuses;

        $data['jobcard'] = JobCard::where('task_id', $task->id)->first();
        $data['existing_jobcard_items'] = $data['jobcard'] && $data['jobcard']->spare_parts ? json_decode($data['jobcard']->spare_parts) : [];
        $data['receipt'] = ReceivableReceipt::where('task_id', $task->id)->first();
        $data['quotation'] = Quotation::where('task_id', $task->id)->first();
        // prx($data['quotation']);
        $data['invoice'] = ManualInvoice::where('task_id', $task->id)->first();
        $data['service_report'] = ServiceReport::where('task_id', $task->id)->first();
        $services = DB::table('items')
            ->where('status', '1')
            ->where(function ($query) use ($storeId) {
                $query->where('store_id', $storeId)
                    ->orWhereRaw("FIND_IN_SET(?, store_ids)", [$storeId]);
            })
            ->get();
        $inventory_items = InventoryItem::where('store_id', $store_id)->get();

        // for invoice generated 
        $data['store'] = $store = Helpers::get_store_data();

        $upcoming_bill_number = Helpers::generateInvoiceId('M', $update = false); // only get .. not update
        $bill_number = $upcoming_bill_number; // Example: 'PJS_M_25-26_82'
        $lastUnderscorePos = strrpos($bill_number, '_');
        $bill_num['prefix'] = substr($bill_number, 0, strrpos($bill_number, '_') + 1); // 'PJS_M_25-26_'
        $bill_num['nongst_prefix'] = Helpers::_storePrefix($store->name);
        $bill_num['number'] = substr($bill_number, strrpos($bill_number, '_') + 1);    // '82'
        $bill_num['non_gst_sno'] = Helpers::get_store_data()->non_gst_sno;


        $dynamicFieldsBySections = [];

        if ($task->formSubmission) {
            $submissionData = json_decode($task->formSubmission->data, true);
            $form = $task->formSubmission->form;

            if ($form) {
                $formStructure = json_decode($form->structure, true);

                foreach ($formStructure as $section) {
                    $sectionData = [
                        'title' => $section['title'] ?? 'Additional Information',
                        'fields' => []
                    ];

                    if (isset($section['fields'])) {
                        foreach ($section['fields'] as $field) {
                            $fieldName = $field['name'];

                            if (isset($submissionData[$fieldName])) {
                                $sectionData['fields'][] = [
                                    'label' => $field['label'] ?? ucfirst(str_replace('_', ' ', $fieldName)),
                                    'value' => $submissionData[$fieldName],
                                    'type' => $field['type'] ?? 'text',
                                    'options' => $field['options'] ?? [],
                                ];
                            }
                        }
                    }

                    if (!empty($sectionData['fields'])) {
                        $dynamicFieldsBySections[] = $sectionData;
                    }
                }
            }
        }

        do {
            $invoice_num = $bill_num['prefix'] . $bill_num['number'];
            $exists = ManualInvoice::where('invoice_id', $invoice_num)->exists();
            if ($exists) {
                $bill_num['number']++;
            }
        } while ($exists);

        $taskTimeCard = null;
        $taskTimeCardEvents = collect();
        if (Schema::hasTable('task_time_cards')) {
            $taskTimeCard = DB::table('task_time_cards')->where('task_id', $task->id)->first();
        }
        if (Schema::hasTable('task_time_card_events')) {
            $taskTimeCardEvents = DB::table('task_time_card_events')
                ->where('task_id', $task->id)
                ->orderByDesc('event_time')
                ->get();
        }

        return view('vendor-views.task.details', compact('inventory_items', 'dynamicFieldsBySections', 'services', 'bill_num', 'tasks', 'data',  'staff', 'statuses', 'task_statuses', 'task_comments', 'task', 'taskTimeCard', 'taskTimeCardEvents'));
    }
    private function formatTaskForJs($task)
    {
        $emp_name =  _vendorOrStaffName($task->created_by);
        return [
            'id' => $task->id,
            'title' => $task->title,
            'created_by_name' => $emp_name,
            'children' => $task->children->map(function ($child) {
                return $this->formatTaskForJs($child);
            })->toArray()
        ];
    }
    public function getTasks(Request $request, $id)
    {
        $tasks = StoreTask::with('allChildren')
            ->where('store_id', Helpers::get_store_id())
            ->where('parent_id', $id)

            ->whereNotNull('parent_id')
            ->get()
            ->map(function ($task) {
                return $this->formatTaskForJs($task);
            });
        return response()->json($tasks);
    }
    public function delete(Request $request)
    {
        StoreTask::find($request->id)->delete();

        Toastr::success('Deleted Successfully');
        return back();
    }


    public function list(Request $request, $empId = null)
    {
        $storeId = Helpers::get_store_id();

        $preset = request('date_range') ?? Cookie::get('date_range')  ?? 'last_30_days';
        if ($request->has('date_range')) {
            Cookie::queue('date_range', $request->date_range, 60 * 24 * 360);
        }
        $custom = request('custom_date_range') ?? null;
        $range = Helpers::calculatePresetDates($preset, $custom);
        $formatted_from  = $range['start'];
        $formatted_to = $range['end'];
        $from = $range['start']->toDateString();
        $to  = $range['end']->toDateString();
        $staff = VendorEmployee::with('role')->where('store_id', $storeId)->whereNot('terminate', 1)->where('status', 1)->get();

        $status =  $request->has('status') && $request->status != 'All' ?  $request->status : '';
        $search =  $request->search ?? '';
        $storeId = Helpers::get_store_id();
        $statuses = StoreTask::where('store_id', $storeId)->where('task_type', 'common')->whereNull('parent_id')->select('status')->distinct()->get();
// prx($statuses);
        $sales = Helpers::task_calendar();

        // ✅ Main list (filtered by date or employee)
        $query = StoreTask::with([
            'user',
            'employee',
            'offeredTo',
            'jobcard',
            'recievableReciept',
            'invoice',
            'quotation',
            'taskCategory'
        ])
            ->where('task_type', 'common')
            ->when($status, function ($q) use ($status) {
                $q->where('status', $status);
            })
            ->when($search, function ($q) use ($search) {
                $q->where('title', 'like', '%' . $search . '%');
            })
            ->where('store_id', $storeId)
            ->whereNull('parent_id');

        if ($empId) {
            $query->where('employee_id', $empId);
        } else {
            $query->whereBetween('created_at', [$formatted_from, $formatted_to]);
        }

        $tasks = $query->get();

        $store = Helpers::get_store_data();
        $configuredStatuses = array_values(array_filter(array_map('trim', explode(',', $store->task_statuses ?? ''))));

        $baseTasksForStats = StoreTask::query()
            ->where('store_id', $storeId)
            ->where('task_type', 'common')
            ->whereNull('parent_id');
         if ($empId) {
            $baseTasksForStats->where('employee_id', $empId);
        } else {
            $baseTasksForStats->whereBetween('created_at', [$formatted_from, $formatted_to]);
        }

        $themeClasses = ['theme-skyblue-color', 'theme-green-color', 'theme-grey-color', 'theme-pink-color', 'theme-purple-color'];
        $taskStatusStatCards = [];
        $seenStatusNormalized = [];
        foreach ($configuredStatuses as $i => $label) {
            $normalized = strtolower(trim($label));
            $seenStatusNormalized[$normalized] = true;
            $count = (clone $baseTasksForStats)->whereRaw('LOWER(TRIM(status)) = ?', [$normalized])->count();
            $taskStatusStatCards[] = [
                'label' => $label,
                'count' => $count,
                'theme_class' => $themeClasses[$i % count($themeClasses)],
            ];
        }

        $appendedFixedStatuses = [
            ['label' => 'New', 'theme_class' => 'theme-skyblue-color'],
            ['label' => 'Completed', 'theme_class' => 'theme-green-color'],
            ['label' => 'Cancelled', 'theme_class' => 'theme-pink-color'],
        ];
        foreach ($appendedFixedStatuses as $row) {
            $normalized = strtolower(trim($row['label']));
            if (! empty($seenStatusNormalized[$normalized])) {
                continue;
            }
            $count = (clone $baseTasksForStats)->whereRaw('LOWER(TRIM(status)) = ?', [$normalized])->count();
            $taskStatusStatCards[] = [
                'label' => $row['label'],
                'count' => $count,
                'theme_class' => $row['theme_class'],
            ];
        }

        return view('vendor-views.task.list', compact('staff', 'sales', 'preset', 'taskStatusStatCards', 'empId', 'tasks', 'statuses', 'from', 'to', 'status'));
    }

    public function export(Request $request) 
    { 
        $storeId = Helpers::get_store_id();

        $query = StoreTask::with([
            'user',
            'employee',
            'taskCategory'
        ])
            ->where('store_id', $storeId)
            ->whereNull('parent_id');
        $tasks = $query->get();

        $headings = [
            'Sl',
            'Title',
            'Description',
            'Client Name',
            'Assigned To',
            'Progress',
            'Task Amount',
            'Time Estimation',
            'Status',
            'Cancelled At',
            'Completed At',
            'Created At',
        ];
        $rows = [];
        foreach ($tasks as $key => $task) {
            $rows[] = [
                $key + 1,
                $task->title,
                $task->description,
                $task->user?->f_name . ' ' . $task->user?->l_name,
                $task->employee?->f_name . ' ' . $task->employee?->l_name . ' (Id:' . $task->employee_id . ')',
                $task->task_amount,
                $task->progress,
                $task->time_count . ' ' . $task->time_unit,
                $task->status,
                $task->cancelled_at,
                $task->completed_at,
                $task->created_at,
            ];
        }

        return Excel::download(new TaskExport($rows, $headings), 'tasks.xlsx');
    }
    public function assigned_tasks(Request $request)
    {
        if (auth('vendor_employee')->check()) {
            $empId = Helpers::get_loggedin_user()->id;
        } else {
            return back();
        }
        $from = $request->from ?? date('Y-m-01');
        $to = $request->to ?? date('Y-m-t');
        $formatted_from = $from . ' 00:00:00';
        $formatted_to = $to . ' 23:59:59';
        $status = $request->status ?? 'All';
        $storeId = Helpers::get_store_id();
        $statuses = StoreTask::where('store_id', $storeId)->where('task_type', 'common')->whereNull('parent_id')->select('status')->distinct()->get();

        $employeeTaskIds = StoreTask::where('employee_id', $empId)
            ->pluck('id')
            ->toArray();

        $tasks = StoreTask::where('task_type', 'common')
            ->where(function ($query) use ($employeeTaskIds, $empId) {
                $query->where(function ($q) use ($employeeTaskIds, $empId) {
                    $q->whereNotNull('parent_id')
                        ->whereNotIn('parent_id', $employeeTaskIds)
                        ->where('offered_to', $empId);
                })
                    ->orWhere(function ($q) use ($empId) {
                        $q->whereNull('parent_id')
                            ->where('offered_to', $empId);
                    });
            })
            ->paginate(10);
        $data = [];
        return view('vendor-views.task.assigned', compact('empId', 'tasks', 'statuses', 'from', 'to', 'status'));
    }
    public function status_new_save(Request $request)
    {
        $store = Store::find(Helpers::get_store_id());
        $store->task_statuses = $request->statuses ?  implode(',', $request->statuses) : null;
        $store->save();
        Toastr::success('Added Successfully');
        return back();
    }
    public function add(Request $request, $project_id = null)
    {
        $store_id = $storeId = Helpers::get_store_id();
        $project = null;
        if ($project_id) {
            $project = Project::find($project_id);
        }
        $departments = Department::where('vendor_id', $store_id)->where('status', '1')->get();
        $rls = EmployeeRole::where('store_id', Helpers::get_store_id())->get();
        $store_id = Helpers::get_store_id();
        $store_data = Helpers::get_store_data();
        $staff = VendorEmployee::with('role')->where('store_id', $store_id)->whereNot('terminate', 1)->where('status', 1)->get();
        $titles = StoreTask::where('store_id', $store_id)->where('task_type', 'common')->select('title')->distinct()->get();
        $data['descriptions'] = StoreTask::where('store_id', $store_id)->where('task_type', 'common')->select('description')->whereNot('description', '')->whereNotNull('description')->distinct()->get();
        $data['task_id'] = Helpers::_generateTaskId();
        // prx($data['task_id']);
        $statuses = Helpers::get_store_data()->task_statuses;
        $data['task_salary_categories'] = TaskSalaryCategory::where('store_id', $store_id)->get();
        $services = DB::table('items')
            ->where('status', '1')
            ->where(function ($query) use ($storeId) {
                $query->where('store_id', $storeId)
                    ->orWhereRaw("FIND_IN_SET(?, store_ids)", [$storeId]);
            })
            ->get();
        return view('vendor-views.task.add', compact('services', 'data', 'departments', 'data', 'rls', 'staff', 'store_data', 'statuses', 'titles', 'project'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required',
            'customer' => 'required_without:project_id',
            'file' => 'nullable|mimes:jpg,jpeg,png,gif,webp,pdf,doc,docx,xls,xlsx,csv|max:5120', // max 5MB
        ]);

        $store_id = Helpers::get_store_id();
        $project  = Project::find($request->project_id);

        // custom header labels 
        $labels =  $request->header_label ?? [];  // or your source
        $fields = $request->header_field ?? [];

        $other_headers = [];

        foreach ($labels as $index => $label) {
            $field = $fields[$index] ?? null;
            $other_headers[$label] = $field;
        }

        $task = new StoreTask();
        $task->store_id =  $store_id;
        $task->task_amount =  $request->task_amount ?? 0;
        if (!$project) {
            $task->task_id = Helpers::_generateTaskId(true);
            $task->user_id =  $request->customer; // store customer
            $task->where_from = $request->where_from;
        } else {
            $task->task_id = null;
            $task->project_id = $project->id;
            $task->user_id =  $project->client_id; // store customer
            $task->where_from = 'project';
        }
        $task->user_type = 'customer';
        $task->progress = $request->progress ?? 0;
        $task->employee_id = $request->employee_id == 0 ? 0 : null;
        $task->offered_to = $request->employee_id == 0 ? null :  $request->employee_id;
        $task->employee_type =  'existing';
        if ($project) {
            $task->task_type = 'project';
        } else {
            $task->task_type = 'common';
        }
        $task->title =  $request->title;
        $task->description = $request->description;
        $task->custom_fields = json_encode($other_headers);

        $task->created_by = auth('vendor')->check() ? 0 : Helpers::get_loggedin_user()->id;

        if ($request->has('file')) {
            $extension = $request->file('file')->getClientOriginalExtension();
            $task->file = Helpers::upload('task/', $extension, $request->file('file'));
        } else {
            $task->file = null;
        }

        $task->time_count = $request->time_count;
        $task->time_unit = $request->time_unit;
        $task->progress = $request->progress ?? 0;
        $task->status = $request->status;
        $task->save();

        DB::beginTransaction();

        $form = Form::where('form_type', 'task_form')
            ->where('store_id', $store_id)
            ->first();

        if ($form) {
            $formFields = json_decode($form->fields, true); // Assuming 'fields' column exists
            $formFieldsById = collect($formFields)->keyBy('id');

            $dynamicData = [];
            foreach ($request->all() as $key => $value) {
                if (preg_match('/^field_(\d+)$/', $key, $matches)) {
                    $fieldId = $matches[1];

                    if (isset($formFieldsById[$fieldId])) {
                        $field = $formFieldsById[$fieldId];
                        $mappedKey = $field['name'] ?? $field['label'] ?? $key;
                        $dynamicData[$mappedKey] = $value;
                    } else {
                        $dynamicData[$key] = $value;
                    }
                }
            }

            if (!empty($dynamicData)) {
                $formSubmission = FormSubmission::create([
                    'form_id' => $form->id,
                    'data' => json_encode($dynamicData),
                ]);

                $task->update([
                    'form_submission_id' => $formSubmission->id
                ]);
            }
        }


        DB::commit();

        $library = app(LibraryController::class);
        $quoteApp = app(QuoteController::class);
        if ($request->has('job_card') && $request->job_card) {

            $jobcard =  $library->job_card($request, 'save', $task->id);
            if (!$jobcard['success']) {
                Toastr::error('Failed to generate job card');
            }
        }
        if ($request->has('receivable_receipt') && $request->receivable_receipt) {
            $jobcard =  $library->recievable_reciept($request, 'save', $task->id);
            if (!$jobcard['success']) {
                Toastr::error('Failed to generate job card');
            }
        }
        if ($request->has('quotation_check') && $request->quotation_check) {
            $jobcard =  $quoteApp->save_info($request, $task->id);
            if (!$jobcard['success']) {
                Toastr::error('Failed to generate quotation');
            }
        }

        Toastr::success('Task Added Successfully');
        if (hasPermission('task', 'view') || (!empty($task->project_id) && hasPermission('project_task', 'view'))) {
            if ($task->parent_id) {
                return redirect()->route('vendor.task.subtask.detail', [$task->id]);
            } else {
                return redirect()->route('vendor.task.detail', [$task->id]);
            }
        } else {
            return back();
        }
    }
    public function reassign(Request $request)
    {
        $task_id = $request->task_id;
        $task = StoreTask::find($task_id);
        $task->offered_to = $request->employee_id;
        $task->employee_id = null;
        $task->save();

        $emp = VendorEmployee::where('id', $request->employee_id)->first();

        // store status update
        $taskStatus = new TaskStatus();
        $taskStatus->task_id = $task->id;
        $taskStatus->store_id = $task->store_id;
        $taskStatus->status = 'Reassign';
        $taskStatus->note = 'Reassigned to ' . ($emp ? $emp->f_name . ' ' . $emp->l_name : ' Staff');
        $taskStatus->created_by  = auth('vendor')->check() ? 0 : Helpers::get_loggedin_user()->id;
        $taskStatus->save();

        Toastr::success('Reassigned Successfully');
        return back();
    }
    public function accept(Request $request, $id)
    {
        $employee_id =  Helpers::get_loggedin_user()->id;
        $task = StoreTask::find($id);
        $task->employee_id = $employee_id;
        $task->save();
        Toastr::success('Accepted Successfully');
        return back();
    }
    public function reject(Request $request, $id)
    {
        $task = StoreTask::find($id);
        $task->offered_to = null;
        $task->save();
        Toastr::success('Task Rejected');
        return back();
    }
    public function task_otp_send(Request $request)
    {
        $task = StoreTask::where('id', $request->task_id)
            ->where('store_id', Helpers::get_store_id())
            ->first();

        if (!$task) {
            return response()->json(['status' => false, 'msg' => 'Task not found']);
        }

        $phone = $task->user?->phone ?? $request->customer_phone;
        if (!$phone) {
            return response()->json(['status' => false, 'msg' => 'Customer phone not found']);
        }

        $jobAction = $request->job_action === 'end' ? 'end' : 'start';

        if ($request->action === 'verify_otp') {
            $otp = implode('', $request->otp ?? []);
            if (!$otp || !_verify_otp($phone, $otp)) {
                return response()->json(['status' => false, 'msg' => 'Invalid OTP']);
            }

            DB::table('phone_otp')->where('phone', $phone)->update(['otp' => null]);

            if ($jobAction === 'end') {
                $task->status = 'Completed';
                $task->completed_at = now();
                $task->save();
                $this->upsertTaskTimeCard($task, 'end');

                $taskStatus = new TaskStatus();
                $taskStatus->task_id = $task->id;
                $taskStatus->store_id = $task->store_id;
                $taskStatus->status = 'Job Ended';
                $taskStatus->note = 'Ended with customer OTP verification';
                $taskStatus->created_by = auth('vendor')->check() ? 0 : Helpers::get_loggedin_user()->id;
                $taskStatus->save();

                return response()->json(['status' => true, 'msg' => 'Job ended successfully']);
            } else {
                if (!in_array($task->status, ['Completed', 'Cancelled'])) {
                    $storeStatuses = array_map('trim', explode(',', Helpers::get_store_data()->task_statuses ?? ''));
                    $startStatus = in_array('In Progress', $storeStatuses) ? 'In Progress' : ($storeStatuses[1] ?? 'In Progress');
                    $task->status = $startStatus ?: 'In Progress';
                    $task->save();
                    $this->upsertTaskTimeCard($task, 'start');

                    $taskStatus = new TaskStatus();
                    $taskStatus->task_id = $task->id;
                    $taskStatus->store_id = $task->store_id;
                    $taskStatus->status = 'Job Started';
                    $taskStatus->note = 'Started with customer OTP verification';
                    $taskStatus->created_by = auth('vendor')->check() ? 0 : Helpers::get_loggedin_user()->id;
                    $taskStatus->save();
                }

                return response()->json(['status' => true, 'msg' => 'Job started successfully']);
            }
        }

        $otp  = rand(1000, 9999);
        DB::table('phone_otp')->updateOrInsert(
            ['phone' => $phone],
            [
                'phone' => $phone,
                'otp' => $otp,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
        _send_confirmation_sms('job_msg', $phone, $otp);

        return response()->json([
            'status' => true,
            'action' => 'otp_sent',
            'job_action' => $jobAction,
            'msg' => 'OTP sent successfully'
        ]);
    }
    public function update(Request $request)
    {
        $request->validate([
            'title' => 'required',
            'file' => 'nullable|mimes:jpg,jpeg,png,gif,webp,pdf,doc,docx,xls,xlsx,csv|max:5120', // max 5MB
        ]);

        $task = StoreTask::find($request->task_id_old);
        if ($task->employee_id != Helpers::get_loggedin_user()->id && !hasPermission('task', 'edit_others')) {
            Toastr::error("You don't have permission to edit other's tasks");
            return back();
        }

        if (in_array($task->status, ['Completed', 'Cancelled'])) {
            Toastr::error('Task already ' . $task->status . ". Can't edit now");
            return back();
        }
        $task->task_amount =  $request->task_amount ?? 0;
        if ($request->customer) {
            $task->user_id =  $request->customer; // store customer
        }
        $task->progress = $request->progress ?? 0;
        $task->employee_type =   'existing';
        $task->title = $request->title;
        $task->status = $request->status;
        $task->description = $request->description;
        $task->employee_id =  ($task->employee_id == $request->employee_id) ? $task->employee_id  : ($request->employee_id == 0 ? 0 :  null);
        $task->offered_to = ($task->employee_id == $request->employee_id)  ? null :  $request->employee_id;

        if ($request->has('file')) {
            if (Storage::disk('public')->exists('task/' . $task['file'])) {
                Storage::disk('public')->delete('task/' . $task['file']);
            }

            $extension = $request->file('file')->getClientOriginalExtension();
            $task->file = Helpers::upload('task/', $extension, $request->file('file'));
        }

        $task->time_count = $request->time_count;
        $task->time_unit = $request->time_unit;
        $task->progress = $request->progress ?? 0;
        // $task->status = $request->status;
        $task->update();

        // dynamic sections 
        $form = Form::where('name', 'task_form')
            ->where('store_id', Helpers::get_store_id())
            ->first();

        if ($form) {
            $dynamicData = [];
            foreach ($request->all() as $key => $value) {
                if (preg_match('/^field_\d+$/', $key)) {
                    $dynamicData[$key] = $value;
                }
            }

            if (!empty($dynamicData)) {
                if ($task->formSubmission) {
                    $task->formSubmission->update([
                        'data' => json_encode($dynamicData),
                        'updated_at' => now(),
                    ]);
                } else {
                    $formSubmission = FormSubmission::create([
                        'form_id' => $form->id,
                        'data' => json_encode($dynamicData),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    $task->update([
                        'form_submission_id' => $formSubmission->id
                    ]);
                }
            }
        }

        DB::commit();

        // save status update 
        $taskStatus = new TaskStatus();
        $taskStatus->task_id = $task->id;
        $taskStatus->store_id = $task->store_id;
        $taskStatus->status = 'Task Edited';
        $taskStatus->created_by  = auth('vendor')->check() ? 0 : Helpers::get_loggedin_user()->id;
        $taskStatus->save();

        Toastr::success('Task Updated Successfully');
        return back();
    }
    public function edit(Request $request, $id)
    {
        $task = StoreTask::with('formSubmission.form')->where('id', $id)->first();

        $project = null;
        if ($task->project_id) {
            $project = Project::find($task->project_id);
        }

        if ($task->employee_id != Helpers::get_loggedin_user()->id && !hasPermission('task', 'edit_others')) {
            Toastr::error("You don't have permission to edit other's tasks");
            return back();
        }

        if (in_array($task->status, ['Completed', 'Cancelled'])) {
            Toastr::error('Task already ' . $task->status . ". Can't edit now");
            return back();
        }
        $existingData = [];

        if ($task->formSubmission) {
            $existingData = json_decode($task->formSubmission->data, true);
        }

        $departments = Department::where('vendor_id', Helpers::get_store_id())->where('status', '1')->get();
        $rls = EmployeeRole::where('store_id', Helpers::get_store_id())->get();
        $store_id = Helpers::get_store_id();
        $store_data = Helpers::get_store_data();
        $staff = VendorEmployee::with('role')->where('store_id', $store_id)->where('status', 1)->get();
        if ($project) {
            $titles = StoreTask::where('store_id', $store_id)->where('task_type', 'project')->select('title')->distinct()->get();
            $data['descriptions'] = StoreTask::where('store_id', $store_id)->where('task_type', 'project')->select('description')->distinct()->get();
        } else {
            $titles = StoreTask::where('store_id', $store_id)->where('task_type', 'common')->select('title')->distinct()->get();
            $data['descriptions'] = StoreTask::where('store_id', $store_id)->where('task_type', 'common')->select('description')->distinct()->get();
        }
        $statuses = Helpers::get_store_data()->task_statuses;
        $data['task_salary_categories'] = TaskSalaryCategory::where('store_id', $store_id)->get();

        $data['where_from'] = DB::table('store_tasks')
            ->where('store_id', $store_id)
            ->distinct()
            ->pluck('where_from');

        return view('vendor-views.task.edit', compact('data', 'project', 'existingData', 'departments', 'task', 'rls', 'staff', 'store_data', 'statuses', 'titles'));
    }
    public function status_update(Request $request)
    {
        // prx($request->all());
        $task = StoreTask::find($request->task_id);

        $rr = ReceivableReceipt::where('task_id', $request->task_id)->first();

        $storeConfig = StoreConfig::where('store_id', Helpers::get_store_id())->first();
        $close_with_otp = ($storeConfig && $storeConfig->close_task_with_otp) ? 1 : 0;

        //  && $rr 
        if ($close_with_otp && ($request->status == 'Completed' || $request->status == 'Cancelled')) {
            $user = StoreCustomer::where('id', $task->user_id)->first();
            if ($user) {
                $phone = $user?->phone;
                if ($request->action == 'verify_otp') {
                    $otp = implode('', $request->otp);
                    $verify =  _verify_otp($phone, $otp);
                    if (!$verify) {
                        return response()->json(['status' => false, 'action' => 'otp_sent', 'status_ajax' => $request->status, 'msg' => 'incorrect otp']);
                    } else {
                        // return response()->json(['status' => true, 'action' => 'otp_verify', 'msg' => 'verified Successdully']);
                    }
                    $delivery_person = $request->employee_id;
                } else {
                    $otp  = rand(1000, 9999);
                    _send_confirmation_sms('mobile_verification', $phone, $otp);
                    DB::table('phone_otp')->updateOrInsert(
                        ['phone' => $phone], // match condition
                        [
                            'otp' => $otp,
                            'created_at' => now()
                        ]
                    );
                    return response()->json(['status' => true, 'action' => 'otp_sent', 'status_ajax' => $request->status, 'msg' => 'otp sent successfully']);
                }
            }
        }

        $store_data = Helpers::get_store_data();
        $statuses = explode(',', $store_data->task_statuses);

        if (!in_array('Completed', $statuses)) {
            $statuses[] = 'Completed';
        }

        if (!in_array('New', $statuses)) {
            array_unshift($statuses, 'New');
        }

        $currentStatus = trim($request->status);
        $currentIndex = array_search($currentStatus, $statuses);
        $totalStatuses = count($statuses);

        $progress = 0;

        if ($currentIndex !== false && $totalStatuses > 1) {
            $progress = round(($currentIndex / ($totalStatuses - 1)) * 100);
        }

        // prx($progress);
        $task->progress = $progress;
        $task->status = $request->status;
        if ($request->status == 'Completed') {
            $task->completed_at = NOW();
        } elseif ($request->status == 'Cancelled') {
            $task->cancelled_at = NOW();
        }
        $task->status = $request->status;
        $task->delivery_person =  $delivery_person ?? null;
        $task->save();

        $normalizedStatus = strtolower(trim($request->status));
        if (in_array($normalizedStatus, ['on hold', 'pause', 'paused'])) {
            $this->upsertTaskTimeCard($task, 'pause');
        } elseif (in_array($normalizedStatus, ['in progress', 'in_progress', 'inprogress'])) {
            $this->upsertTaskTimeCard($task, 'resume');
        } elseif (in_array($normalizedStatus, ['completed', 'cancelled'])) {
            $this->upsertTaskTimeCard($task, 'end');
        }

        if ($rr) {
            $rr->delivered = 1;
            $rr->delivery_person =  $delivery_person ?? null;
            $rr->delivered_at = NOW();
            $rr->save();
        }

        $taskStatus = new TaskStatus();
        $taskStatus->task_id = $task->id;
        $taskStatus->store_id = $task->store_id;
        $taskStatus->status = $request->status;
        $taskStatus->note = $request->note;

        if ($request->hasFile('file')) {
            $uploadedFiles = $request->file('file');
            $storedPaths = [];
            foreach ($uploadedFiles as $file) {
                $extension = $file->getClientOriginalExtension();
                $storedPaths[] = Helpers::upload('task/', $extension, $file);
            }
            $taskStatus->file = json_encode($storedPaths);
        } else {
            $taskStatus->file = null;
        }
        if ($request->hasFile('webcam_file')) {
            $uploadedFiles = $request->file('webcam_file');
            $storedPaths = [];
            foreach ($uploadedFiles as $file) {
                $extension = $file->getClientOriginalExtension();
                $storedPaths[] = Helpers::upload('task/', $extension, $file);
            }
            $taskStatus->webcam_file = json_encode($storedPaths);
        } else {
            $taskStatus->webcam_file = null;
        }

        $taskStatus->created_by  = auth('vendor')->check() ? 0 : Helpers::get_loggedin_user()->id;
        $taskStatus->save();

        Toastr::success('Task Status Updated Successfully');
        if ($request->request_type == 'ajax') {
            return response()->json(['status' => true, 'action' => '', 'msg' => 'Status changed successfully']);
        }

        return redirect()->back();
    }
    public function setting_update(Request $request)
    {
        // prx($request->all());
        StoreConfig::updateOrInsert(['store_id' => Helpers::get_store_id()], [
            'close_task_with_otp' => $request->close_task_with_otp,
            'task_id_serial' => $request->task_id_serial ?? '',
            'task_id_format' => $request->task_id_format ?? '',
            'task_quotation' => $request->task_quotation ?? 0,
            'task_invoice' => $request->task_invoice ?? 0,
            'task_recievable_receipt' => $request->task_recievable_receipt ?? 0,
            'task_service_reports' => $request->task_service_reports ?? 0,
        ]);
        Toastr::success('Updated Successfully');
        return back();
    }
    public function setting(Request $request)
    {
        $storeConfig = StoreConfig::where('store_id', Helpers::get_store_id())->first();
        return view('vendor-views.task.setting', compact('storeConfig'));
    }
    public function save_progress(Request $request)
    {
        $task = StoreTask::find($request->task_id);
        $task->progress = $request->progress ?? 0;
        $task->save();

        // save status update 
        $taskStatus = new TaskStatus();
        $taskStatus->task_id = $task->id;
        $taskStatus->store_id = $task->store_id;
        $taskStatus->status = 'Progress Updated';
        $taskStatus->note = 'Progress updated to ' . $task->progress . '%';
        $taskStatus->created_by  = auth('vendor')->check() ? 0 : Helpers::get_loggedin_user()->id;
        $taskStatus->save();
    }
    public function comment_add(Request $request)
    {
        $request->validate([
            'title' =>  'required|string|max:255',
            'comment' =>  'required',
            'files.*' => 'nullable|mimes:jpg,jpeg,png,gif,pdf,doc,docx,xls,xlsx,csv|max:2048', // max 2MB
        ]);
        $files = [];
        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $img) {
                $extension = $img->getClientOriginalExtension();
                $file = Helpers::upload('task/', $extension, $img);
                $files[] = $file;
            }
        }
        $files = json_encode($files);

        $comment = new TaskComment();
        $comment->store_id = Helpers::get_store_id();
        $comment->task_id = $request->task_id;
        $comment->title = $request->title;
        $comment->comment = $request->comment;
        $comment->files = $files;
        $comment->created_by = auth('vendor')->check() ? 0 : Helpers::get_loggedin_user()->id;
        $comment->save();

        Toastr::success('Added Successfully');
        return back();
    }
    public function comment_update(Request $request)
    {
        $request->validate([
            'title' =>  'required|string|max:255',
            'comment' =>  'required',
            'files.*' => 'nullable|mimes:jpg,jpeg,png,gif,pdf,doc,docx,xls,xlsx,csv|max:2048', // max 2MB
        ]);
        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $img) {
                $extension = $img->getClientOriginalExtension();
                $file = Helpers::upload('task/', $extension, $img);
                $files[] = $file;
            }
        }
        $comment =  TaskComment::findOrFail($request->comment_id);

        $existingFiles = json_decode($comment->files, true) ?? [];

        $uploadedFiles = [];

        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $img) {
                $extension = $img->getClientOriginalExtension();
                $file = Helpers::upload('task/', $extension, $img); // Your custom upload logic
                $uploadedFiles[] = $file;
            }
        }

        $allFiles = array_merge($existingFiles, $uploadedFiles);
        $comment->title = $request->title;
        $comment->comment = $request->comment;
        $comment->files = json_encode($allFiles);
        $comment->update();

        Toastr::success('Files uploaded successfully.');
        return back();
    }
    public function comment_delete(Request $request, $comment_id)
    {
        $comment  = TaskComment::findOrFail($comment_id);
        $comment->delete();
        Toastr::success('Deleted successfully.');
        return back();
    }
    public function pic_delete(Request $request, $comment_id, $fileToDelete)
    {
        $comment  = TaskComment::findOrFail($comment_id);

        $files = json_decode($comment->files, true);

        $files = array_filter($files, function ($file) use ($fileToDelete) {
            return $file !== $fileToDelete;
        });

        $files = array_values($files);

        $comment->files = json_encode($files);
        $comment->save();

        $path = storage_path('app/public/task/' . $fileToDelete); // 👈 use storage_path, not asset()
        if (file_exists($path)) {
            unlink($path);
        }

        Toastr::success('Deleted Successfully');
        return back();
    }
    public function comment_edit(Request $request, $id)
    {
        $comment = TaskComment::find($id);
        $task = StoreTask::where('id', $comment->task_id)->first();
        return view('vendor-views.task.comment_edit', compact('comment', 'task'));
    }
    public function update_level(Request $request)
    {
        // $subtask = StoreTask::find($request->child_id);
        // $subtask->parent_id = $request->parent_id;
        // $subtask->save();
        // return response()->json(['status' => true]);

        $child = StoreTask::find($request->child_id);
        $parent = StoreTask::find($request->parent_id);

        if ($parent && $parent->parent_id == $child->id) {
            return response()->json(['status' => false, 'message' => 'Cannot assign child as parent.']);
        }

        $child->parent_id = $request->parent_id;
        $child->save();

        return response()->json(['status' => true]);
    }
    function deleteTaskWithChildren($id)
    {
        $task = StoreTask::find($id);

        if (!$task) return;

        // Get all direct children
        $children = StoreTask::where('parent_id', $id)->get();

        foreach ($children as $child) {
            $this->deleteTaskWithChildren($child->id); // recursive delete
        }

        $task->delete(); // finally delete the parent
    }
    public function delete_subtask(Request $request, $id)
    {
        $this->deleteTaskWithChildren($id);
        $tasks = StoreTask::with('allChildren')
            ->where('store_id', Helpers::get_store_id())
            ->where('parent_id', $id)

            ->whereNotNull('parent_id')
            ->get()
            ->map(function ($task) {
                return $this->formatTaskForJs($task);
            });
        return response()->json($tasks);
    }
    public function subtask_add(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'parent_id' => 'required',
            'title' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)]);
        }
        $parentTask = StoreTask::find($request->parent_id);
        $task_type = $parentTask->task_type;
        $subtask = new StoreTask();
        $subtask->store_id = Helpers::get_store_id();
        $subtask->parent_id = $request->parent_id;
        $subtask->task_type = $task_type;
        $subtask->title = $request->title;
        $subtask->employee_id = $request->employee_id;
        $subtask->created_by = auth('vendor')->check() ? 0 : Helpers::get_loggedin_user()->id;
        $subtask->save();

        return response()->json(['status' => true, 'id' => $subtask->id,  'msg' => 'Added Successfully']);
    }
}
