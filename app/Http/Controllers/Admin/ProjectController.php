<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Brian2694\Toastr\Facades\Toastr;
use App\Models\Project;
use App\CentralLogics\Helpers;
use App\Models\AccountOption;
use App\Models\Admin;
use App\Models\Department;
use App\Models\ProjectAttachment;
use App\Models\ProjectComment;
use App\Models\ProjectTask;
use App\Models\ProjectDepartment;
use App\Models\ProjectInternalNote;
use App\Models\ProjectMilestone;
use App\Models\ProjectTeamMember; 
use App\Models\StoreConfig;
use App\Models\StoreTask;
use Illuminate\Support\Facades\Cookie;

class ProjectController extends Controller
{

    public function dashboard(Request $request)
    {
        $projectNames = Project::pluck('project_title')->toArray();
        $progress = Project::pluck('prog_percent')->toArray();
        $stats['total_projects'] = Project::count();
        $stats['delayed_projects'] = Project::where('progress_status', 'Open')->count();
        $stats['inProgress_projects'] = Project::where('progress_status', 'Open')->where('end_date', '<', date('Y-m-d'))->count();
        $stats['completed_projects'] = Project::where('progress_status', 'Completed')->count();
        return view('admin-views.project.dashboard', compact('projectNames', 'progress', 'stats'));
    }
    public function settings(Request $request)
    {
        $storeConfig = StoreConfig::where('store_id', 0)->first();
        return view('admin-views.project.setting', compact('storeConfig'));
    }
    public function add(Request $request)
    {
        $storeId = 0;
        $employees = Admin::where('role_id', '!=', 1)->with(['role'])->get();
        $departments = Department::where('vendor_id', $storeId)->get();
        $categories = AccountOption::where('store_id', $storeId)->where('type', 'project_category')->get();
        return view('admin-views.project.add_project', compact('employees', 'departments', 'categories'));
    }
    public function edit(Request $request, $id)
    {
        $storeId = 0;
        $project = Project::with('teamMembers', 'departments', 'milestones', 'attachments', 'client')->where('vendor_id', $storeId)->where('id', $id)->first();
        $employees = Admin::where('role_id', '!=', 1)->with(['role'])->get();
        $departments = Department::where('vendor_id', $storeId)->get();
        $categories = AccountOption::where('store_id', $storeId)->where('type', 'project_category')->get();
        return view('admin-views.project.edit_project', compact('employees', 'departments', 'project', 'categories'));
    }
    public function task_list(Request $request, $project_id = null)
    {
        $storeId = 0;
        $tasks  = StoreTask::with('offeredTo', 'employee')
            ->when($project_id, function ($q) use ($project_id) {
                $q->where('project_id', $project_id);
            })
            ->where('store_id', $storeId)->where('task_type', 'project')->whereNull('parent_id')->get();

        $project = Project::find($project_id);
        return view('admin-views.project.task_list', compact('tasks', 'project'));
    }
    public function index(Request $request, $empId = null)
    {

        $storeId = 0;
        $preset = request('date_range') ?? 'this_year';
        $custom = request('custom_date_range') ?? null;
        $range = Helpers::calculatePresetDates($preset, $custom);
        $formatted_from  = $range['start'];
        $formatted_to = $range['end'];

        $status = $request->status ?? '';
        $type = $request->type ?? '';
        $size = $request->size ?? '';
        $search = $request->search ?? '';

        if ($empId) {
            $projects = Project::with('client', 'tasks', 'teamMembers')->where('team_leader', $empId)->where('vendor_id', 0)->get();
        } else {
            $projects = Project::with('client', 'tasks', 'teamMembers')->where('vendor_id', 0)
                ->whereBetween('created_at', [$formatted_from, $formatted_to])
                ->when($search, function ($q) use ($search) {
                    $q->where('project_title', 'like', '%' . $search . '%');
                });

            if (isset($request->status) && $request->status != '') {
                $projects = $projects->where('progress_status', $request->status);
            }
            if (isset($request->type) && $request->type != '') {
                $projects = $projects->where('project_type', $request->type);
            }
            if (isset($request->size) && $request->size != '') {
                $projects = $projects->where('project_size', $request->size);
            }
            $projects = $projects->get();
        }


        $employees = Admin::where('role_id', '!=', 1)->with(['role'])->get();

        return view('admin-views.project.index', compact('empId', 'projects', 'employees', 'preset'));
    }


    public function save_task(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'project_id' => 'required',
            'desctiption' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)]);
        }

        $task = new ProjectTask;
        $task->project_id =  $request->project_id;
        $task->prog_status =  'New';
        $task->title = $request->post('title');
        $task->time_count = $request->post('time_count');
        $task->time_unit = $request->post('time_unit');
        $task->desctiption = $request->desctiption;
        $task->time_estimation = $request->time_estimation;

        if ($task->save()) {
            return response()->json(['status' => true, 'message' => 'Task Added Successfully']);
        } else {
            return response()->json(['status' => false, 'message' => 'Some Error Occured']);
        }
    }
    public function save_comment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'project_id' => 'required',
            'desctiption' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)]);
        }

        $task = new ProjectComment;
        $task->project_id =  $request->project_id;
        $task->description = $request->desctiption;
        $images = [];
        if (!empty($request->file('images'))) {
            foreach ($request->images as $img) {
                $image_name = Helpers::upload('project/comments/', 'png', $img);
                $images[] = $image_name;
            }
            $task->images = json_encode($images);
        }
        if ($task->save()) {
            return response()->json(['status' => true, 'message' => 'Comment Added Successfully']);
        } else {
            return response()->json(['status' => false, 'message' => 'Some Error Occured']);
        }
    }

    public function udpate_team(Request $request)
    {
        $project_id = $request->project_id;
        $oldMembers = ProjectTeamMember::where('project_id', $project_id)
            ->where('member_type', 'admin')
            ->pluck('employee_id')->toArray();
        $newMembers = $request->team_members ?? [];

        // insert new
        foreach ($newMembers as $empId) {
            if (!in_array($empId, $oldMembers)) {
                ProjectTeamMember::create([
                    'project_id' => $project_id,
                    'employee_id' => $empId,
                    'member_type' => 'admin'
                ]);
            }
        }

        // delete removed 
        foreach ($oldMembers as $empId) {
            if (!in_array($empId, $newMembers)) {
                ProjectTeamMember::where('project_id', $project_id)
                    ->where('employee_id', $empId)
                    ->where('member_type', 'admin')
                    ->delete();
            }
        }
        Toastr::success('Team Updated Successfully');
        return redirect()->route('admin.project.details', [$project_id, 'team']);
    }
    public function save_team(Request $request)
    {
        $project = Project::find($request->project_id);
        $teams = [];

        if ($project->teams) {
            $teams = json_decode($project->teams, true);
        }

        $new_team = [
            'team_id' => $request->team_id ?? round(microtime(true) * 1000),
            'team_name' => $request->team_name,
            'team_members' => $request->team_members
        ];

        $found = false;
        foreach ($teams as &$team) {
            if (isset($team['team_id']) && $team['team_id'] == $new_team['team_id']) {
                $team = $new_team;
                $found = true;
                break;
            }
        }
        unset($team);

        if (!$found) {
            $teams[] = $new_team;
        }

        $project->teams = json_encode($teams);
        $project->update();


        Toastr::success('Team Created Successfully');
        return back();
    }
    public function tasks_list(Request $request)
    {
        $storeId = 0;

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
        $staff = Admin::where('role_id', '!=', 1)->with('role')->whereNot('terminate', 1)->where('status', 1)->get();
        $status =  $request->has('status') && $request->status != 'All' ?  $request->status : '';
        $search =  $request->search ?? '';
        $storeId = 0;
        $statuses = StoreTask::where('store_id', $storeId)->where('task_type', 'common')->whereNull('parent_id')->select('status')->distinct()->get();

        $sales = Helpers::task_calendar();


        // ✅ Main list (filtered by date or employee)
        $query = StoreTask::with([
            'employee',
            'offeredTo',
        ])
            ->where('task_type', 'project')
            ->when($status, function ($q) use ($status) {
                $q->where('status', $status);
            })
            ->when($search, function ($q) use ($search) {
                $q->where('title', 'like', '%' . $search . '%');
            })
            ->where('store_id', $storeId)
            ->whereNull('parent_id');


        $query->whereBetween('created_at', [$formatted_from, $formatted_to]);


        $tasks = $query->get();

        // Fetch all tasks for the store (no date filter)
        $allTasks = StoreTask::where('store_id', $storeId)
            ->whereNull('parent_id')
            ->get();

        // Initialize counts
        $data = [
            'alotted' => 0,
            'inprogress' => 0,
            'completed' => 0,
            'cancelled' => 0,
        ];

        // Loop through tasks and count based on rules
        foreach ($allTasks as $task) {
            if ($task->employee_id) {
                $data['alotted']++;
            } elseif (in_array($status, ['in progress', 'in_progress', 'inprogress'])) {
                $data['inprogress']++;
            } elseif (strtolower($task->status) == 'completed') {
                $data['completed']++;
            } elseif (strtolower($task->status) == 'cancelled') {
                $data['cancelled']++;
            }
        }

        // prx(0);
        // prx($tasks);
        return view('admin-views.project.task_list', compact('staff', 'sales', 'preset', 'data',  'tasks', 'statuses', 'from', 'to', 'status'));
    }
    public function details(Request $request)
    {
        $store_id = 0;

        $project =  Project::with('tasks', 'client', 'projectManager', 'attachments', 'teamMembers.admin', 'internalNotes')->where('id', $request->id)->first();
        $titles = ProjectTask::where('project_id', $project['id'])->select('title')->distinct()->get();
        $team_leader = Admin::find($project['team_leader']);
        $assigned_to = Admin::find($project['team_members']);
        $tasks = ProjectTask::where('project_id', $project['id'])->get();
        $comments = ProjectComment::where('project_id', $project['id'])->get(); 
        $employees = Admin::where('role_id', '!=', 1)->with(['role'])->get();

        return view('admin-views.project.details', compact('project', 'titles', 'employees', 'team_leader', 'assigned_to', 'tasks', 'comments'));
    }
    public function delete_comment(Request $request, $id)
    {
        ProjectComment::find($id)->delete();
        Toastr::success('Comment Deleted Successfully');
        return back();
    }
    public function task_status(Request $request)
    {
        ProjectTask::find($request->task_id)->update(['prog_status' => $request->status]);
        return true;
    }
    public function status_change(Request $request)
    {
        $project = Project::find($request->id);

        $statuses = ['New', 'Open', 'On Hold', 'In Progress', 'Completed', 'Cancelled'];

        $currentStatus = trim($request->status);
        $currentIndex = array_search($currentStatus, $statuses);
        $totalStatuses = count($statuses);

        $progress = 0;

        if ($currentIndex !== false && $totalStatuses > 1) {
            $progress = round(($currentIndex / ($totalStatuses - 1)) * 100);
        }

        // prx($progress);
        $project->prog_percent = $progress;

        $project->status = $request->status;
        $project->save();
        Toastr::success('Project Status Changed Successfully');
        return back();
    }
    public function progress_status_change(Request $request)
    {
        $project = Project::find($request->id);
        $statuses = ['New', 'Open', 'On Hold', 'In Progress', 'Cancelled', 'Completed'];

        if ($statuses != 'Cancelled') {
            $currentStatus = trim($request->status);
            $currentIndex = array_search($currentStatus, $statuses);
            $totalStatuses = count($statuses);

            $progress = 0;

            if ($currentIndex !== false && $totalStatuses > 1) {
                $progress = round(($currentIndex / ($totalStatuses - 1)) * 100);
            }
            $project->prog_percent = $progress;
        }

        $project->progress_status = $request->status;
        $project->save();
        Toastr::success('Project Status Changed Successfully');
        return back();
    }

    public function prog_update(Request $request)
    {
        $coupon = Project::find($request->pr_id);
        $coupon->progress_status = $request->prog_status;
        $coupon->prog_percent = $request->prog_percent;
        $coupon->save();
        Toastr::success('Project Status Changed Successfully');
        return back();
    }
    public function delete(Request $request, $id)
    {
        ProjectDepartment::where('project_id', $id)->delete();
        ProjectTeamMember::where('project_id', $id)->delete();
        ProjectInternalNote::where('project_id', $id)->delete();
        ProjectMilestone::where('project_id', $id)->delete();
        StoreTask::where('project_id', $id)->delete();
        ProjectAttachment::where('project_id', $id)->delete();

        Project::where('id', $id)->delete();
        return back();
    }


    public function save_info_old(Request $request)
    {
        $id = $request->post('project_id');

        if ($id == '') { // for new project

            $validator = Validator::make($request->all(), [
                'title' => 'required|max:255',
                'team_leader' => 'required',
                'start_date' => 'required',
                'end_date' => 'required',
                'project_size' => 'required',
                'project_type' => 'required',
            ], [
                'title.required' => 'Please Enter Project Title',
                'team_members.required' => 'Please Assign Memebers to Project',
            ]);

            if ($validator->fails()) {
                return back()->withErrors($validator)->withInput();
            }
            $project = new Project;
            $project->vendor_id = 0;
        } else {
            $project = Project::find($id);
        }

        $project->client_id = $request->post('customer');
        $project->project_title = $request->post('title');
        // $project->team_members = implode(',',$request->post('team_members'));
        $project->team_leader = $request->post('team_leader');
        $project->progress_status = $request->post('prog_status');
        $project->project_size = $request->post('project_size');
        $project->project_type = $request->post('project_type');
        $project->prog_percent = $request->post('prog_percent');
        $project->start_date = $request->post('start_date');
        $project->end_date = $request->post('end_date');
        $project->file = $request->has('file') ? Helpers::upload('project/', 'png', $request->file('file')) : '';
        $project->specifications = $request->post('specifications');
        $project->cost = $request->post('cost_est');
        $project->advance_pay = $request->post('advance_pay');
        $project->status = $request->post('status');
        $project->created_at = date('Y-m-d H:i:s');
        $project->save();

        Toastr::success('Saved Successfully');
        if(hasPermission('project', 'list')) {
            return redirect()->route('admin.project.all');
        }else{
            return back();
        }
    }
    public function save_info(Request $request)
    {
        $id = $request->post('project_id');

        $validator = Validator::make($request->all(), [
            'title'             => 'required|max:255',
            'project_manager'   => 'required',
            'start_date'        => 'required',
            'end_date'          => 'required',
            'project_size'      => 'required',
            'project_type'      => 'required',

            'milestones.*.title'     => 'nullable|string|max:255',
            'milestones.*.due_date'  => 'nullable|date',
            'milestones.*.status'    => 'nullable|string',

            'attachments.*' => 'nullable|mimes:pdf,jpg,jpeg,png,xls,xlsx,doc,docx|max:30720',
        ], [
            'title.required' => 'Please Enter Project Title',
            'project_manager.required' => 'Please Assign a Project Manager',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }


        if (!$id) {
            $project = new Project;
            $project->vendor_id = 0;
            $project->created_at = now();
        } else {
            $project = Project::find($id);
        }

        $project->client_id        = $request->customer_id;
        $project->project_title    = $request->title;
        $project->project_category = $request->project_category;
        $project->priority         = $request->priority;
        $project->project_manager  = $request->project_manager;
        $project->progress_status  = $request->progress_status ?? 'New';
        $project->project_size     = $request->project_size;
        $project->project_type     = $request->project_type;
        $project->prog_percent     = $request->prog_percent ?? 0;
        $project->start_date       = $request->start_date;
        $project->end_date         = $request->end_date;
        $project->duration_count   = $request->duration_count;
        $project->duration_type    = $request->duration_type;
        $project->payment_type     = $request->payment_type;
        $project->cost             = $request->cost_estimate;
        $project->payment_terms    = $request->payment_terms;
        $project->short_description = $request->short_description;
        $project->specifications    = $request->detailed_specs;
        $project->advance_pay       = $request->advance_pay;
        // $project->internal_notes    = $request->internal_notes;
        $project->tags_labels       = $request->tags ? implode(',', $request->tags) : null;

        if ($request->hasFile('file')) {
            $project->file = Helpers::upload('project/', 'png', $request->file('file'));
        }

        $project->updated_at = now();
        $project->save();

        // category
        AccountOption::firstOrCreate(
            [
                'store_id' => 0,
                'name' => $request->project_category,
                'type' => 'project_category'
            ]
        );

        $incomingMilestones = collect($request->milestones ?? []);

        $incomingIds = $incomingMilestones->pluck('id')->filter()->map(function ($v) {
            return (int) $v;
        })->toArray();

        $oldMilestones = ProjectMilestone::where('project_id', $project->id)->get()->keyBy('id');

        foreach ($incomingMilestones as $m) {
            $hasTitle = isset($m['title']) && trim($m['title']) !== '';
            $hasId = isset($m['id']) && $m['id'];

            if ($hasId) {
                $mid = (int) $m['id'];
                if (isset($oldMilestones[$mid])) {
                    $updateData = [];

                    if (array_key_exists('title', $m) && $m['title'] !== null) {
                        $updateData['title'] = $m['title'];
                    }
                    if (array_key_exists('due_date', $m) && $m['due_date'] !== null && $m['due_date'] !== '') {
                        $updateData['due_date'] = $m['due_date'];
                    }
                    if (array_key_exists('status', $m) && $m['status'] !== null) {
                        $updateData['status'] = $m['status'];
                    }

                    if (!empty($updateData)) {
                        $oldMilestones[$mid]->update($updateData);
                    }
                }
                continue;
            }

            if (!$hasId && $hasTitle) {
                ProjectMilestone::create([
                    'project_id' => $project->id,
                    'title'      => $m['title'],
                    'due_date'   => $m['due_date'] ?? null,
                    'status'     => $m['status'] ?? null,
                ]);
            }
        }

        // delete milestones removed in the ui
        foreach ($oldMilestones as $oldId => $oldModel) {
            if (!in_array((int) $oldId, $incomingIds)) {
                $oldModel->delete();
            }
        }


        $oldMembers = ProjectTeamMember::where('project_id', $project->id)
            ->where('member_type', 'admin')
            ->pluck('employee_id')->toArray();
        $newMembers = $request->team_members ?? [];

        // insert new
        foreach ($newMembers as $empId) {
            if (!in_array($empId, $oldMembers)) {
                ProjectTeamMember::create([
                    'project_id' => $project->id,
                    'employee_id' => $empId,
                    'member_type' => 'admin'
                ]);
            }
        } 

        // delete removed
        foreach ($oldMembers as $empId) {
            if (!in_array($empId, $newMembers)) {
                ProjectTeamMember::where('project_id', $project->id)
                    ->where('employee_id', $empId)
                    ->where('member_type', 'admin')
                    ->delete();
            }
        }
        $oldNotes = ProjectInternalNote::where('project_id', $project->id)
            ->pluck('id')
            ->toArray();

        $newNotes = $request->internal_notes ?? [];

        ProjectInternalNote::where('project_id', $project->id)->delete();

        // Insert new notes
        foreach ($newNotes as $noteText) {
            if (!empty(trim($noteText))) {
                ProjectInternalNote::create([
                    'project_id' => $project->id,
                    'note'       => $noteText,
                ]);
            }
        }
        $oldDeps = ProjectDepartment::where('project_id', $project->id)->pluck('department_id')->toArray();
        $newDeps = $request->departments ?? [];

        foreach ($newDeps as $depId) {
            if (!in_array($depId, $oldDeps)) {
                ProjectDepartment::create([
                    'project_id'    => $project->id,
                    'department_id' => $depId
                ]);
            }
        }

        foreach ($oldDeps as $depId) {
            if (!in_array($depId, $newDeps)) {
                ProjectDepartment::where('project_id', $project->id)
                    ->where('department_id', $depId)
                    ->delete();
            }
        }

        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {

                $extension = $file->getClientOriginalExtension();
                $file_name = Helpers::upload('store/project_attachments/', $extension, $file);

                ProjectAttachment::create([
                    'project_id' => $project->id,
                    'file_path'  => 'store/project_attachments/',
                    'file_name'  => $file_name,
                    'file_type'  => $extension,
                ]);
            }
        }

        // -----------------------------------------------------
        Toastr::success('Project Saved Successfully');
        if(hasPermission('project', 'list')) {
            return redirect()->route('admin.project.all');
        }else{
            return back();
        }
    }



    public function store_milestone(Request $request)
    {
        // prx($request->all());
        $project_id = $request->project_id;
        foreach ($request->milestones as $m) {

            if (!$m['title']) continue; // skip empty rows

            ProjectMilestone::create([
                'project_id' => $project_id,
                'title'      => $m['title'],
                'due_date'   => $m['due_date'],
                'status'     => $m['status'],
            ]);
        }
        Toastr::success('Milestone Saved Successfully');
        return redirect()->route('admin.project.details', [$project_id, 'milestones']);
    }

    public function milestone_delete(Request $request, $id)
    {
        $milestone = ProjectMilestone::find($id);

        if ($milestone) {
            $milestone->delete();
        }

        Toastr::success('Milestone deleted successfully');
        return back();
    }
    public function milestone_status_change(Request $request, $id)
    {
        $status = $request->status;
        ProjectMilestone::find($id)->update(['status' => $status]);
        Toastr::success('Milestone status updated successfully');
        return back();
    }

    public function store_note(Request $request)
    {
        $project_id = $request->project_id;


        ProjectInternalNote::create([
            'project_id' => $project_id,
            'note'      => $request->note,
        ]);
        Toastr::success('Note Saved Successfully');
        return redirect()->route('admin.project.details', [$project_id, 'notes']);
    }
}
