<?php

use App\Modules\HMIS\Controllers\Vendor\DentalIntakeController;

// One-screen intake for dental clinics: patient + today's visit together. The controller turns
// the screen away unless the store's Hospital Settings category is Dental.
Route::group(['prefix' => 'dental-intake', 'as' => 'dental-intake.'], function () {
    Route::get('',      [DentalIntakeController::class, 'create'])->name('create')->middleware('permission:opd_register,add');
    Route::post('store',[DentalIntakeController::class, 'store'])->name('store')->middleware('permission:opd_register,add');
    // Who already uses the number being typed — one number covers a whole family, so the desk
    // is shown the matches before a second record is created for someone who already exists.
    Route::get('lookup-phone', [DentalIntakeController::class, 'lookupPhone'])->name('lookup-phone')->middleware('permission:opd_register,add');
});
