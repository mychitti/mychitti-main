<?php

use Illuminate\Support\Facades\Route;
use App\Modules\School\Controllers\Vendor\AdmissionController;

Route::group(['prefix' => 'admissions', 'as' => 'admissions.'], function () {
    Route::get('/',                [AdmissionController::class, 'index'])->name('index')->middleware('permission:admissions,view,admissions,add,admissions,edit');
    Route::get('create',           [AdmissionController::class, 'create'])->name('create')->middleware('permission:admissions,add');
    Route::post('store',           [AdmissionController::class, 'store'])->name('store')->middleware('permission:admissions,add');
    Route::get('{id}',             [AdmissionController::class, 'show'])->name('show')->middleware('permission:admissions,view');
    Route::get('{id}/edit',        [AdmissionController::class, 'edit'])->name('edit')->middleware('permission:admissions,edit');
    Route::post('{id}/update',     [AdmissionController::class, 'update'])->name('update')->middleware('permission:admissions,edit');
    Route::post('{id}/status',     [AdmissionController::class, 'update_status'])->name('status')->middleware('permission:admissions,edit');
    Route::get('{id}/convert',     [AdmissionController::class, 'convert'])->name('convert')->middleware('permission:admissions,add');
    Route::get('{id}/delete',      [AdmissionController::class, 'delete'])->name('delete')->middleware('permission:admissions,delete');
});
