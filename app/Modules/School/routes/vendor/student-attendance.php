<?php

use Illuminate\Support\Facades\Route;
use App\Modules\School\Controllers\Vendor\StudentAttendanceController;

Route::group(['prefix' => 'student-attendance', 'as' => 'student-attendance.'], function () {
    Route::get('/',       [StudentAttendanceController::class, 'mark'])->name('mark')->middleware('permission:student_attendance,view,student_attendance,add');
    Route::post('store',  [StudentAttendanceController::class, 'store'])->name('store')->middleware('permission:student_attendance,add');
    Route::get('report',  [StudentAttendanceController::class, 'report'])->name('report')->middleware('permission:student_attendance,view');
});
