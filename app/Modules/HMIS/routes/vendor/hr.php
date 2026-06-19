<?php

use App\Http\Controllers\Vendor\AssetsController;
use App\Modules\HMIS\Controllers\Vendor\AttendanceController;
use App\Modules\HMIS\Controllers\Vendor\BasicStaffController;
use App\Modules\HMIS\Controllers\Vendor\EmployeeController;
use App\Modules\HMIS\Controllers\Vendor\HRController;
use App\Modules\HMIS\Controllers\Vendor\LeaveController;
use App\Modules\HMIS\Controllers\Vendor\SalaryController;
use App\Modules\HMIS\Controllers\Vendor\ShiftController;
use App\Modules\HMIS\Controllers\Vendor\StaffController;
use App\Modules\HMIS\Controllers\Vendor\VendorEmployeeController;

// Basic staff — free tier
Route::group(['prefix' => 'basic-staff', 'as' => 'basic-staff.'], function () {
    Route::get('/',              [BasicStaffController::class, 'index'])->name('index');
    Route::get('create',         [BasicStaffController::class, 'create'])->name('create');
    Route::post('store',         [BasicStaffController::class, 'store'])->name('store');
    Route::get('edit/{id}',      [BasicStaffController::class, 'edit'])->name('edit');
    Route::post('update/{id}',   [BasicStaffController::class, 'update'])->name('update');
    Route::delete('delete/{id}', [BasicStaffController::class, 'destroy'])->name('delete');
    Route::get('roles',              [BasicStaffController::class, 'roles'])->name('roles');
});

Route::group(['prefix' => 'attendance', 'as' => 'attendance.'], function () {
    Route::get('report',      [AttendanceController::class, 'report'])->name('report');
    Route::get('export',      [AttendanceController::class, 'export'])->name('export')->middleware('permission:attendance_report,export');
    Route::get('list',        [AttendanceController::class, 'index'])->name('all')->middleware('permission:attendance_manage,list');
    Route::post('save',       [AttendanceController::class, 'save_att'])->name('save')->middleware('permission:attendance_manage,edit');
    Route::get('manage/{id}', [AttendanceController::class, 'manage'])->name('manage')->middleware('permission:attendance_manage,view');
});

Route::group(['prefix' => 'leave', 'as' => 'leave.'], function () {
    Route::get('list',                    [LeaveController::class, 'index'])->name('all');
    Route::get('add',                     [LeaveController::class, 'add'])->name('add-new')->middleware('permission:leave_manage,add');
    Route::get('status/{id}/{status}',    [LeaveController::class, 'status'])->name('status')->middleware('permission:leave_manage,status_change');
    Route::post('save-info',              [LeaveController::class, 'save_info'])->name('save-info')->middleware('permission:leave_manage,add');
    Route::post('save',                   [LeaveController::class, 'save_leave'])->name('save')->middleware('permission:leave_manage,add');
    Route::get('manage/{id}',             [LeaveController::class, 'manage'])->name('manage');
});

Route::group(['prefix' => 'salary', 'as' => 'salary.'], function () {
    Route::get('generate-monthly/{month}', [SalaryController::class, 'generate_monthly'])->name('generate-monthly')->middleware('permission:salary_manage,generate');
    Route::get('mark-paid/{month}',        [SalaryController::class, 'mark_paid'])->name('mark-paid')->middleware('permission:salary_manage,mark_paid');
    Route::get('report',                   [SalaryController::class, 'report'])->name('report');
    Route::get('export-salaries',          [SalaryController::class, 'export_salaries'])->name('export-salaries')->middleware('permission:salary_manage,export');
    Route::get('list',                     [SalaryController::class, 'index'])->name('list');
    Route::get('export',                   [SalaryController::class, 'export'])->name('export')->middleware('permission:salary_manage,export');
    Route::post('get-info',                [SalaryController::class, 'get_info'])->name('get-info');
    Route::post('salary-history',          [SalaryController::class, 'my_salary_history'])->name('salary-history');
    Route::post('pay',                     [SalaryController::class, 'pay'])->name('pay')->middleware('permission:salary_manage,mark_paid');
    Route::get('add',                      [SalaryController::class, 'add'])->name('add-new')->middleware('permission:salary_manage,add');
    Route::get('status/{id}/{status}',     [SalaryController::class, 'status'])->name('status')->middleware('permission:salary_manage,status_change');
    Route::post('save-info',               [SalaryController::class, 'save_info'])->name('save')->middleware('permission:salary_manage,edit');
    Route::get('delete/{id}',              [SalaryController::class, 'delete'])->name('delete')->middleware('permission:salary_manage,delete');
    Route::get('edit/{id}',                [SalaryController::class, 'edit'])->name('edit')->middleware('permission:salary_manage,edit');
    Route::get('all-advance-requests',     [SalaryController::class, 'all_advance_requests'])->name('all-advance-requests')->middleware('permission:advance_requests,list');
    Route::get('approve-advance/{id}',     [SalaryController::class, 'approve_advance_payment'])->name('approve-advance')->middleware('permission:advance_requests,approve');
    Route::get('reject-advance/{id}',      [SalaryController::class, 'reject_advance_payment'])->name('reject-advance')->middleware('permission:advance_requests,reject');
});

Route::get('advance-payment', [SalaryController::class, 'advance_payment'])->name('advance-payment');
Route::post('salary/advance-request/store', [SalaryController::class, 'advance_request_store'])->name('salary.advance-request.store');

Route::group(['prefix' => 'staff-department', 'as' => 'staff-department.'], function () {
    Route::get('/',              [StaffController::class, 'departments'])->name('all');
    Route::post('store',         [StaffController::class, 'store_department'])->name('store');
    Route::post('status-change', [StaffController::class, 'status_change'])->name('status-change');
    Route::get('delete/{id}',    [StaffController::class, 'delete_department'])->name('delete');
});

Route::group(['prefix' => 'staff', 'as' => 'staff.'], function () {
    Route::get('list',               [StaffController::class, 'index'])->name('list')->middleware('permission:staff_manage,list');
    Route::get('edit/{id}',          [StaffController::class, 'edit'])->name('edit')->middleware('permission:staff_manage,edit');
    Route::post('save-info',         [StaffController::class, 'save_info'])->name('save')->middleware('permission:staff_manage,add');
    Route::get('add',                [StaffController::class, 'add'])->name('add')->middleware('permission:staff_manage,add');
    Route::get('settings',           [StaffController::class, 'settings'])->name('settings')->middleware('permission:staff_manage,settings');
    Route::post('save-settings',     [StaffController::class, 'save_settings'])->name('settings.save')->middleware('permission:staff_manage,settings');
    Route::get('status/{id}/{status}',[StaffController::class, 'status'])->name('status')->middleware('permission:staff_manage,status_change');
    Route::get('delete/{id}',        [StaffController::class, 'delete'])->name('delete')->middleware('permission:staff_manage,delete');

    Route::group(['prefix' => 'team', 'as' => 'team.'], function () {
        Route::get('/',                  [StaffController::class, 'teams'])->name('index');
        Route::post('save',              [StaffController::class, 'team_save'])->name('save')->middleware('permission:staff_team,add');
        Route::get('delete/{id}',        [StaffController::class, 'team_delete'])->name('delete')->middleware('permission:staff_team,delete');
        Route::get('member-delete/{id}', [StaffController::class, 'team_member_delete'])->name('member.delete')->middleware('permission:staff_team,edit');
        Route::get('edit/{id}',          [StaffController::class, 'team_edit'])->name('edit')->middleware('permission:staff_team,edit');
        Route::post('update',            [StaffController::class, 'team_update'])->name('update')->middleware('permission:staff_team,edit');
        Route::get('view/{id}',          [StaffController::class, 'team_edit'])->name('view')->middleware('permission:staff_team,view');
    });
});

Route::group(['prefix' => 'shifts', 'as' => 'shifts.'], function () {
    Route::get('/',           [ShiftController::class, 'index'])->name('index');
    Route::post('store',      [ShiftController::class, 'store'])->name('store')->middleware('permission:shift_manage,add');
    Route::get('delete/{id}', [ShiftController::class, 'delete'])->name('delete')->middleware('permission:shift_manage,delete');
    Route::post('update',     [ShiftController::class, 'update'])->name('update')->middleware('permission:shift_manage,edit');

    // Live Work Updates + shift change (swap)
    Route::get('live',               [ShiftController::class, 'liveWork'])->name('live');
    Route::post('swap/store',        [ShiftController::class, 'swapStore'])->name('swap.store');
    Route::get('swap/{id}/{status}', [ShiftController::class, 'swapStatus'])->name('swap.status');
});

Route::group(['prefix' => 'hr', 'as' => 'hr.'], function () {
    Route::get('dashboard', [HRController::class, 'dashboard'])->name('dashboard')->middleware('permission:hr_manage,dashboard');
});

Route::group(['prefix' => 'employee', 'as' => 'employee.'], function () {
    Route::post('resign',             [VendorEmployeeController::class, 'leave_save'])->name('resign');
    Route::get('/clock-in',           [VendorEmployeeController::class, 'clock_in'])->name('clockin');
    Route::get('/clock-out',          [VendorEmployeeController::class, 'clock_out'])->name('clockout');
    Route::get('/attendance',         [VendorEmployeeController::class, 'attendance'])->name('employee-attendance');
    Route::get('/salary-history',     [VendorEmployeeController::class, 'my_salary_history'])->name('salary-history');
    Route::get('/leaves',             [VendorEmployeeController::class, 'leaves'])->name('employee-leave');
    Route::post('/leave-save',        [VendorEmployeeController::class, 'leave_save'])->name('leave-save');
    Route::get('/approve-leave/{id}', [VendorEmployeeController::class, 'leave_approve'])->name('approve-leave');
    Route::get('/reject-leave/{id}',  [VendorEmployeeController::class, 'leave_reject'])->name('reject-leave');
    Route::get('add-new',             [EmployeeController::class, 'add_new'])->name('add-new')->middleware('permission:staff_manage,add');
    Route::post('add-new',            [EmployeeController::class, 'store'])->middleware('permission:staff_manage,add');
    Route::get('list',                [EmployeeController::class, 'list'])->name('list');
    Route::get('edit/{id}',           [EmployeeController::class, 'edit'])->name('edit')->middleware('permission:staff_manage,edit');
    Route::post('update/{id}',        [EmployeeController::class, 'update'])->name('update')->middleware('permission:staff_manage,edit');
    Route::delete('delete/{id}',      [EmployeeController::class, 'delete'])->name('delete')->middleware('permission:staff_manage,delete');
    Route::get('export-employee',     [EmployeeController::class, 'export_employee'])->name('export-employee')->middleware('permission:staff_manage,export');
    Route::get('view/{id}',           [EmployeeController::class, 'view'])->name('view')->middleware('permission:staff_manage,view');
    Route::get('view-id-card/{id}',   [EmployeeController::class, 'view_id_card'])->name('view-id-card')->middleware('permission:staff_manage,view');
    Route::get('terminate/{id}',      [EmployeeController::class, 'terminate'])->name('terminate')->middleware('permission:staff_manage,terminate');
    Route::post('comment-save',       [EmployeeController::class, 'comment_save'])->name('comment-save')->middleware('permission:staff_manage,comment');
    Route::get('resignation-action/{id}/{action}', [EmployeeController::class, 'resignation_action'])->name('resignation-action');
});

Route::group(['prefix' => 'asset', 'as' => 'asset.'], function () {
    Route::post('return',       [AssetsController::class, 'return_asset'])->name('return');
    Route::get('alotted',       [AssetsController::class, 'alotted_assets'])->name('alotted');
    Route::get('/',             [AssetsController::class, 'index'])->name('index');
    Route::post('store',        [AssetsController::class, 'store'])->name('store');
    Route::post('alot',         [AssetsController::class, 'alotToStaff'])->name('alot');
    Route::get('delete/{id}',   [AssetsController::class, 'delete'])->name('delete');
    Route::post('get-alotment-details', [AssetsController::class, 'get_alotment_details'])->name('get-alotment-details');
});
