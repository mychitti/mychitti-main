<?php

use App\Http\Controllers\Vendor\AssetsController;

// Basic staff — free tier (no HR subscription required)
Route::group(['prefix' => 'basic-staff', 'as' => 'basic-staff.'], function () {
    Route::get('/',              [\App\Modules\Laundry\Controllers\Vendor\BasicStaffController::class, 'index'])->name('index');
    Route::get('create',         [\App\Modules\Laundry\Controllers\Vendor\BasicStaffController::class, 'create'])->name('create');
    Route::post('store',         [\App\Modules\Laundry\Controllers\Vendor\BasicStaffController::class, 'store'])->name('store');
    Route::get('edit/{id}',      [\App\Modules\Laundry\Controllers\Vendor\BasicStaffController::class, 'edit'])->name('edit');
    Route::post('update/{id}',   [\App\Modules\Laundry\Controllers\Vendor\BasicStaffController::class, 'update'])->name('update');
    Route::delete('delete/{id}', [\App\Modules\Laundry\Controllers\Vendor\BasicStaffController::class, 'destroy'])->name('delete');
    Route::get('roles',              [\App\Modules\Laundry\Controllers\Vendor\BasicStaffController::class, 'roles'])->name('roles');
    Route::post('roles/store',       [\App\Modules\Laundry\Controllers\Vendor\BasicStaffController::class, 'storeRole'])->name('roles.store');
    Route::post('roles/update/{id}', [\App\Modules\Laundry\Controllers\Vendor\BasicStaffController::class, 'updateRole'])->name('roles.update');
    Route::delete('roles/delete/{id}',[\App\Modules\Laundry\Controllers\Vendor\BasicStaffController::class, 'destroyRole'])->name('roles.delete');
});

Route::group(['prefix' => 'attendance', 'as' => 'attendance.'], function () {
    Route::get('report',      [\App\Modules\Laundry\Controllers\Vendor\AttendanceController::class, 'report'])->name('report');
    Route::get('export',      [\App\Modules\Laundry\Controllers\Vendor\AttendanceController::class, 'export'])->name('export')->middleware('permission:attendance_report,export');
    Route::get('list',        [\App\Modules\Laundry\Controllers\Vendor\AttendanceController::class, 'index'])->name('all')->middleware('permission:attendance_manage,list');
    Route::post('save',       [\App\Modules\Laundry\Controllers\Vendor\AttendanceController::class, 'save_att'])->name('save')->middleware('permission:attendance_manage,edit');
    Route::get('manage/{id}', [\App\Modules\Laundry\Controllers\Vendor\AttendanceController::class, 'manage'])->name('manage')->middleware('permission:attendance_manage,view');
});

Route::group(['prefix' => 'leave', 'as' => 'leave.'], function () {
    Route::get('list',           [\App\Modules\Laundry\Controllers\Vendor\LeaveController::class, 'index'])->name('all');
    Route::get('add',            [\App\Modules\Laundry\Controllers\Vendor\LeaveController::class, 'add'])->name('add-new')->middleware('permission:leave_manage,add');
    Route::get('status/{id}/{status}', [\App\Modules\Laundry\Controllers\Vendor\LeaveController::class, 'status'])->name('status')->middleware('permission:leave_manage,status_change');
    Route::post('save-info',     [\App\Modules\Laundry\Controllers\Vendor\LeaveController::class, 'save_info'])->name('save-info')->middleware('permission:leave_manage,add');
    Route::post('save',          [\App\Modules\Laundry\Controllers\Vendor\LeaveController::class, 'save_leave'])->name('save')->middleware('permission:leave_manage,add');
    Route::get('manage/{id}',    [\App\Modules\Laundry\Controllers\Vendor\LeaveController::class, 'manage'])->name('manage');
});

Route::group(['prefix' => 'salary', 'as' => 'salary.'], function () {
    Route::get('generate-monthly/{month}', [\App\Modules\Laundry\Controllers\Vendor\SalaryController::class, 'generate_monthly'])->name('generate-monthly')->middleware('permission:salary_manage,generate');
    Route::get('mark-paid/{month}',        [\App\Modules\Laundry\Controllers\Vendor\SalaryController::class, 'mark_paid'])->name('mark-paid')->middleware('permission:salary_manage,mark_paid');
    Route::get('report',                   [\App\Modules\Laundry\Controllers\Vendor\SalaryController::class, 'report'])->name('report');
    Route::get('export-salaries',          [\App\Modules\Laundry\Controllers\Vendor\SalaryController::class, 'export_salaries'])->name('export-salaries')->middleware('permission:salary_manage,export');
    Route::get('list',                     [\App\Modules\Laundry\Controllers\Vendor\SalaryController::class, 'index'])->name('list');
    Route::get('export',                   [\App\Modules\Laundry\Controllers\Vendor\SalaryController::class, 'export'])->name('export')->middleware('permission:salary_manage,export');
    Route::post('get-info',                [\App\Modules\Laundry\Controllers\Vendor\SalaryController::class, 'get_info'])->name('get-info');
    Route::post('salary-history',          [\App\Modules\Laundry\Controllers\Vendor\SalaryController::class, 'my_salary_history'])->name('salary-history');
    Route::post('pay',                     [\App\Modules\Laundry\Controllers\Vendor\SalaryController::class, 'pay'])->name('pay')->middleware('permission:salary_manage,mark_paid');
    Route::get('add',                      [\App\Modules\Laundry\Controllers\Vendor\SalaryController::class, 'add'])->name('add-new')->middleware('permission:salary_manage,add');
    Route::get('status/{id}/{status}',     [\App\Modules\Laundry\Controllers\Vendor\SalaryController::class, 'status'])->name('status')->middleware('permission:salary_manage,status_change');
    Route::post('save-info',               [\App\Modules\Laundry\Controllers\Vendor\SalaryController::class, 'save_info'])->name('save')->middleware('permission:salary_manage,edit');
    Route::get('delete/{id}',              [\App\Modules\Laundry\Controllers\Vendor\SalaryController::class, 'delete'])->name('delete')->middleware('permission:salary_manage,delete');
    Route::get('edit/{id}',                [\App\Modules\Laundry\Controllers\Vendor\SalaryController::class, 'edit'])->name('edit')->middleware('permission:salary_manage,edit');
    Route::get('all-advance-requests',     [\App\Modules\Laundry\Controllers\Vendor\SalaryController::class, 'all_advance_requests'])->name('all-advance-requests')->middleware('permission:advance_requests,list');
    Route::get('approve-advance/{id}',     [\App\Modules\Laundry\Controllers\Vendor\SalaryController::class, 'approve_advance_payment'])->name('approve-advance')->middleware('permission:advance_requests,approve');
    Route::get('reject-advance/{id}',      [\App\Modules\Laundry\Controllers\Vendor\SalaryController::class, 'reject_advance_payment'])->name('reject-advance')->middleware('permission:advance_requests,reject');
});

Route::get('advance-payment', [\App\Modules\Laundry\Controllers\Vendor\SalaryController::class, 'advance_payment'])->name('advance-payment');
Route::post('salary/advance-request/store', [\App\Modules\Laundry\Controllers\Vendor\SalaryController::class, 'advance_request_store'])->name('salary.advance-request.store');

Route::group(['prefix' => 'staff-department', 'as' => 'staff-department.'], function () {
    Route::get('/',               [\App\Modules\Laundry\Controllers\Vendor\StaffController::class, 'departments'])->name('all');
    Route::post('store',          [\App\Modules\Laundry\Controllers\Vendor\StaffController::class, 'store_department'])->name('store');
    Route::post('status-change',  [\App\Modules\Laundry\Controllers\Vendor\StaffController::class, 'status_change'])->name('status-change');
    Route::get('delete/{id}',     [\App\Modules\Laundry\Controllers\Vendor\StaffController::class, 'delete_department'])->name('delete');
});

Route::group(['prefix' => 'staff', 'as' => 'staff.'], function () {
    Route::get('list',             [\App\Modules\Laundry\Controllers\Vendor\StaffController::class, 'index'])->name('list')->middleware('permission:staff_manage,list');
    Route::get('edit/{id}',        [\App\Modules\Laundry\Controllers\Vendor\StaffController::class, 'edit'])->name('edit')->middleware('permission:staff_manage,edit');
    Route::post('save-info',       [\App\Modules\Laundry\Controllers\Vendor\StaffController::class, 'save_info'])->name('save')->middleware('permission:staff_manage,add');
    Route::get('add',              [\App\Modules\Laundry\Controllers\Vendor\StaffController::class, 'add'])->name('add')->middleware('permission:staff_manage,add');
    Route::get('settings',         [\App\Modules\Laundry\Controllers\Vendor\StaffController::class, 'settings'])->name('settings')->middleware('permission:staff_manage,settings');
    Route::post('save-settings',   [\App\Modules\Laundry\Controllers\Vendor\StaffController::class, 'save_settings'])->name('settings.save')->middleware('permission:staff_manage,settings');
    Route::get('status/{id}/{status}', [\App\Modules\Laundry\Controllers\Vendor\StaffController::class, 'status'])->name('status')->middleware('permission:staff_manage,status_change');
    Route::get('delete/{id}',      [\App\Modules\Laundry\Controllers\Vendor\StaffController::class, 'delete'])->name('delete')->middleware('permission:staff_manage,delete');

    Route::group(['prefix' => 'team', 'as' => 'team.'], function () {
        Route::get('/',              [\App\Modules\Laundry\Controllers\Vendor\StaffController::class, 'teams'])->name('index');
        Route::post('save',          [\App\Modules\Laundry\Controllers\Vendor\StaffController::class, 'team_save'])->name('save')->middleware('permission:staff_team,add');
        Route::get('delete/{id}',    [\App\Modules\Laundry\Controllers\Vendor\StaffController::class, 'team_delete'])->name('delete')->middleware('permission:staff_team,delete');
        Route::get('member-delete/{id}', [\App\Modules\Laundry\Controllers\Vendor\StaffController::class, 'team_member_delete'])->name('member.delete')->middleware('permission:staff_team,edit');
        Route::get('edit/{id}',      [\App\Modules\Laundry\Controllers\Vendor\StaffController::class, 'team_edit'])->name('edit')->middleware('permission:staff_team,edit');
        Route::post('update',        [\App\Modules\Laundry\Controllers\Vendor\StaffController::class, 'team_update'])->name('update')->middleware('permission:staff_team,edit');
        Route::get('view/{id}',      [\App\Modules\Laundry\Controllers\Vendor\StaffController::class, 'team_edit'])->name('view')->middleware('permission:staff_team,view');
    });
});

Route::group(['prefix' => 'shifts', 'as' => 'shifts.'], function () {
    Route::get('/',          [\App\Modules\Laundry\Controllers\Vendor\ShiftController::class, 'index'])->name('index');
    Route::post('store',     [\App\Modules\Laundry\Controllers\Vendor\ShiftController::class, 'store'])->name('store')->middleware('permission:shift_manage,add');
    Route::get('delete/{id}',[\App\Modules\Laundry\Controllers\Vendor\ShiftController::class, 'delete'])->name('delete')->middleware('permission:shift_manage,delete');
    Route::post('update',    [\App\Modules\Laundry\Controllers\Vendor\ShiftController::class, 'update'])->name('update')->middleware('permission:shift_manage,edit');
});

Route::group(['prefix' => 'hr', 'as' => 'hr.'], function () {
    Route::get('dashboard', [\App\Modules\Laundry\Controllers\Vendor\HRController::class, 'dashboard'])->name('dashboard')->middleware('permission:hr_manage,dashboard');
});

Route::group(['prefix' => 'employee', 'as' => 'employee.'], function () {
    Route::post('resign',            [\App\Modules\Laundry\Controllers\Vendor\VendorEmployeeController::class, 'leave_save'])->name('resign');
    Route::get('/clock-in',          [\App\Modules\Laundry\Controllers\Vendor\VendorEmployeeController::class, 'clock_in'])->name('clockin');
    Route::get('/clock-out',         [\App\Modules\Laundry\Controllers\Vendor\VendorEmployeeController::class, 'clock_out'])->name('clockout');
    Route::get('/attendance',        [\App\Modules\Laundry\Controllers\Vendor\VendorEmployeeController::class, 'attendance'])->name('employee-attendance');
    Route::get('/salary-history',    [\App\Modules\Laundry\Controllers\Vendor\VendorEmployeeController::class, 'my_salary_history'])->name('salary-history');
    Route::get('/leaves',            [\App\Modules\Laundry\Controllers\Vendor\VendorEmployeeController::class, 'leaves'])->name('employee-leave');
    Route::post('/leave-save',       [\App\Modules\Laundry\Controllers\Vendor\VendorEmployeeController::class, 'leave_save'])->name('leave-save');
    Route::get('/approve-leave/{id}',[\App\Modules\Laundry\Controllers\Vendor\VendorEmployeeController::class, 'leave_approve'])->name('approve-leave');
    Route::get('/reject-leave/{id}', [\App\Modules\Laundry\Controllers\Vendor\VendorEmployeeController::class, 'leave_reject'])->name('reject-leave');
    Route::get('add-new',            [\App\Modules\Laundry\Controllers\Vendor\EmployeeController::class, 'add_new'])->name('add-new')->middleware('permission:staff_manage,add');
    Route::post('add-new',           [\App\Modules\Laundry\Controllers\Vendor\EmployeeController::class, 'store'])->middleware('permission:staff_manage,add');
    Route::get('list',               [\App\Modules\Laundry\Controllers\Vendor\EmployeeController::class, 'list'])->name('list');
    Route::get('edit/{id}',          [\App\Modules\Laundry\Controllers\Vendor\EmployeeController::class, 'edit'])->name('edit')->middleware('permission:staff_manage,edit');
    Route::post('update/{id}',       [\App\Modules\Laundry\Controllers\Vendor\EmployeeController::class, 'update'])->name('update')->middleware('permission:staff_manage,edit');
    Route::delete('delete/{id}',     [\App\Modules\Laundry\Controllers\Vendor\EmployeeController::class, 'delete'])->name('delete')->middleware('permission:staff_manage,delete');
    Route::get('export-employee',    [\App\Modules\Laundry\Controllers\Vendor\EmployeeController::class, 'export_employee'])->name('export-employee')->middleware('permission:staff_manage,export');
    Route::get('view/{id}',          [\App\Modules\Laundry\Controllers\Vendor\EmployeeController::class, 'view'])->name('view')->middleware('permission:staff_manage,view');
    Route::get('view-id-card/{id}',  [\App\Modules\Laundry\Controllers\Vendor\EmployeeController::class, 'view_id_card'])->name('view-id-card')->middleware('permission:staff_manage,view');
    Route::get('terminate/{id}',     [\App\Modules\Laundry\Controllers\Vendor\EmployeeController::class, 'terminate'])->name('terminate')->middleware('permission:staff_manage,terminate');
    Route::post('comment-save',      [\App\Modules\Laundry\Controllers\Vendor\EmployeeController::class, 'comment_save'])->name('comment-save')->middleware('permission:staff_manage,comment');
    Route::get('resignation-action/{id}/{action}', [\App\Modules\Laundry\Controllers\Vendor\EmployeeController::class, 'resignation_action'])->name('resignation-action');
});

Route::group(['prefix' => 'asset', 'as' => 'asset.'], function () {
    Route::post('return',        [AssetsController::class, 'return_asset'])->name('return');
    Route::get('alotted',        [AssetsController::class, 'alotted_assets'])->name('alotted');
    Route::get('/',              [AssetsController::class, 'index'])->name('index');
    Route::post('store',         [AssetsController::class, 'store'])->name('store');
    Route::post('alot',          [AssetsController::class, 'alotToStaff'])->name('alot');
    Route::get('delete/{id}',    [AssetsController::class, 'delete'])->name('delete');
    Route::post('get-alotment-details', [AssetsController::class, 'get_alotment_details'])->name('get-alotment-details');
});
