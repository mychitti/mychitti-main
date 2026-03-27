<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use App\Models\Lead;
use App\Models\EmployeeRole;
use App\Models\VendorEmployee;
use Brian2694\Toastr\Facades\Toastr;
use App\CentralLogics\Helpers;
use App\Models\Department;
use App\Models\Staff;
use App\Models\Store;
use App\Models\StoreCustomer;
use App\Models\StoreEnabledModule;
use App\Models\StoreTask;
use App\Models\TaskComment;
use App\Models\TaskSalaryCategory;
use App\Models\TaskStatus;
use App\Models\TempEmployee;
use App\Models\Vendor;
use Illuminate\Support\Facades\Storage;

class TaskSalaryCategoryController extends Controller
{
    public function index()
    {
        $storeId = Helpers::get_store_id();
        $categories = TaskSalaryCategory::where('store_id', $storeId)->paginate(10);
        return view('vendor-views.task_salary_categories.index', compact('categories'));
    }
    public function show($id)
    {
        abort(404); // or return redirect()->back();
    }
    public function create()
    {
        return view('vendor-views.task_salary_categories.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'amount' => 'required|numeric',
        ]);

        $taskCat = new TaskSalaryCategory();
        $taskCat->name = $request->name;
        $taskCat->amount = $request->amount;
        $taskCat->store_id = Helpers::get_store_id();
        $taskCat->save();

        return redirect()->back();
    }

    public function edit(TaskSalaryCategory $taskSalaryCategory)
    {
        return view('vendor-veiws.task_salary_categories.edit', compact('taskSalaryCategory'));
    }

    public function update(Request $request, TaskSalaryCategory $taskSalaryCategory)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'amount' => 'required|numeric',
        ]);

        $taskSalaryCategory->update($request->only('name', 'amount'));

        return redirect()->route('vendor.task-salary-categories.index')->with('success', 'Category updated.');
    }

    public function destroy(TaskSalaryCategory $taskSalaryCategory)
    {
        $taskSalaryCategory->delete();
        return redirect()->route('vendor.task-salary-categories.index')->with('success', 'Category deleted.');
    }
}
