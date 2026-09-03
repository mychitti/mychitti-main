<?php

use App\Modules\HMIS\Controllers\Vendor\HospitalActivityLogController;
use App\Modules\HMIS\Controllers\Vendor\HospitalDashboardController;

Route::get('hospital/activity-log',  [HospitalActivityLogController::class, 'index'])->name('hospital.activity-log')->middleware('permission:hospital_manage,activity_log');

Route::get('hospital/dashboard',     [HospitalDashboardController::class, 'index'])->name('hospital.dashboard')->middleware('permission:hospital_manage,dashboard');
Route::get('hospital/staff-dashboard',[HospitalDashboardController::class, 'index'])->name('hospital.staff-dashboard')->middleware('permission:hospital_manage,dashboard');
Route::get('hospital/settings',      [HospitalDashboardController::class, 'settings'])->name('hospital.settings')->middleware('permission:hospital_manage,settings');
Route::post('hospital/settings',     [HospitalDashboardController::class, 'saveSettings'])->name('hospital.settings.save')->middleware('permission:hospital_manage,settings');
Route::post('hospital/settings/daily-report/test', [HospitalDashboardController::class, 'testDailyReport'])->name('hospital.daily-report.test')->middleware('permission:hospital_manage,settings');

// Per-department letterhead: address, GSTIN and licence book for lab / pharmacy / radiology.
Route::post('hospital/settings/department/{department}', [HospitalDashboardController::class, 'saveDepartment'])->name('hospital.department.save')->middleware('permission:hospital_manage,settings');
