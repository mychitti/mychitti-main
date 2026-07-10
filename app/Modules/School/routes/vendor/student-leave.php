<?php

use Illuminate\Support\Facades\Route;
use App\Modules\School\Controllers\Vendor\StudentLeaveController;

Route::group(['prefix' => 'student-leave', 'as' => 'student-leave.'], function () {
    Route::get('/',           [StudentLeaveController::class, 'index'])->name('index')->middleware('permission:student_leave,view,student_leave,add,student_leave,approve,student_leave,reject');
    Route::get('create',      [StudentLeaveController::class, 'create'])->name('create')->middleware('permission:student_leave,add');
    Route::post('store',      [StudentLeaveController::class, 'store'])->name('store')->middleware('permission:student_leave,add');
    Route::get('{id}/approve', [StudentLeaveController::class, 'approve'])->name('approve')->middleware('permission:student_leave,approve');
    Route::post('{id}/reject', [StudentLeaveController::class, 'reject'])->name('reject')->middleware('permission:student_leave,reject');
    Route::get('{id}/delete',  [StudentLeaveController::class, 'delete'])->name('delete')->middleware('permission:student_leave,delete');
});
 