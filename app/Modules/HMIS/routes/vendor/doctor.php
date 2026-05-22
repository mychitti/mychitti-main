<?php

use App\Modules\HMIS\Controllers\Vendor\DoctorController;
use App\Modules\HMIS\Controllers\Vendor\MyDoctorProfileController;
 
Route::group(['prefix' => 'doctor', 'as' => 'doctor.'], function () {
    Route::get('list',                               [DoctorController::class, 'index'])->name('list')->middleware('permission:staff_doctor,list');
    Route::get('export',                             [DoctorController::class, 'export'])->name('export')->middleware('permission:staff_doctor,export');
    Route::get('create',                             [DoctorController::class, 'create'])->name('create')->middleware('permission:staff_doctor,add');
    Route::post('store',                             [DoctorController::class, 'store'])->name('store')->middleware('permission:staff_doctor,add');
    Route::get('{id}/edit',                          [DoctorController::class, 'edit'])->name('edit')->middleware('permission:staff_doctor,edit');
    Route::post('{id}/update',                       [DoctorController::class, 'update'])->name('update')->middleware('permission:staff_doctor,edit');
    Route::post('{id}/delete',                       [DoctorController::class, 'destroy'])->name('delete')->middleware('permission:staff_doctor,delete');
    Route::get('{id}/slots',                         [DoctorController::class, 'slots'])->name('slots')->middleware('permission:staff_doctor,slots');
    Route::post('{id}/slots/store',                  [DoctorController::class, 'slotStore'])->name('slot.store')->middleware('permission:staff_doctor,slots');
    Route::get('{id}/slots/{slot_id}/toggle',        [DoctorController::class, 'slotToggle'])->name('slot.toggle')->middleware('permission:staff_doctor,slots');
    Route::get('{id}/slots/{slot_id}/delete',        [DoctorController::class, 'slotDestroy'])->name('slot.delete')->middleware('permission:staff_doctor,slots');
    Route::post('{id}/slots/{slot_id}/clone',        [DoctorController::class, 'slotClone'])->name('slot.clone')->middleware('permission:staff_doctor,slots');
});

Route::group(['prefix' => 'my-doctor-profile', 'as' => 'my-doctor-profile.'], function () {
    Route::get('edit',                               [MyDoctorProfileController::class, 'edit'])->name('edit');
    Route::post('update',                            [MyDoctorProfileController::class, 'update'])->name('update');
    Route::post('slot/store',                        [MyDoctorProfileController::class, 'slotStore'])->name('slot.store');
    Route::get('slot/{slot_id}/toggle',              [MyDoctorProfileController::class, 'slotToggle'])->name('slot.toggle');
    Route::get('slot/{slot_id}/delete',              [MyDoctorProfileController::class, 'slotDestroy'])->name('slot.delete');
    Route::post('slot/{slot_id}/clone',              [MyDoctorProfileController::class, 'slotClone'])->name('slot.clone');
});
