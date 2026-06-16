<?php

use App\Modules\HMIS\Controllers\Vendor\NurseController;
use App\Modules\HMIS\Controllers\Vendor\MyNurseProfileController;

// Nurse self-service — the logged-in nurse's own assigned patients.
Route::group(['prefix' => 'my-nurse-profile', 'as' => 'my-nurse-profile.'], function () {
    Route::get('patients', [MyNurseProfileController::class, 'patients'])->name('patients');
});

Route::group(['prefix' => 'nurse', 'as' => 'nurse.'], function () {
    Route::get('list',       [NurseController::class, 'index'])->name('list');
    Route::get('export',     [NurseController::class, 'export'])->name('export')->middleware('permission:staff_nurse,export');
    Route::get('create',     [NurseController::class, 'create'])->name('create')->middleware('permission:staff_nurse,add');
    Route::post('store',     [NurseController::class, 'store'])->name('store')->middleware('permission:staff_nurse,add');
    Route::get('{id}',       [NurseController::class, 'show'])->name('show')->middleware('permission:staff_nurse,view');
    Route::get('{id}/edit',  [NurseController::class, 'edit'])->name('edit')->middleware('permission:staff_nurse,edit');
    Route::post('{id}/update',[NurseController::class, 'update'])->name('update')->middleware('permission:staff_nurse,edit');
    Route::get('{id}/delete',[NurseController::class, 'destroy'])->name('delete')->middleware('permission:staff_nurse,delete');
});
