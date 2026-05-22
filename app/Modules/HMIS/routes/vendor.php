<?php

use App\Modules\HMIS\Controllers\Vendor\DoctorController;
use Illuminate\Support\Facades\Route;

// Doctor list and add are free — no hospital_manage plan required
Route::get('doctor/list',   [DoctorController::class, 'index'])->name('doctor.list')->middleware('permission:staff_doctor,list');
Route::get('doctor/create', [DoctorController::class, 'create'])->name('doctor.create')->middleware('permission:staff_doctor,add');
Route::post('doctor/store', [DoctorController::class, 'store'])->name('doctor.store')->middleware('permission:staff_doctor,add');

Route::group(['middleware' => ['planwise:hospital_manage']], function () {
    require __DIR__ . '/vendor/hospital.php';
    require __DIR__ . '/vendor/doctor.php';
    require __DIR__ . '/vendor/appointment.php';
    require __DIR__ . '/vendor/prescription.php';
    require __DIR__ . '/vendor/nurse.php';
    require __DIR__ . '/vendor/ward.php';
    require __DIR__ . '/vendor/opd.php';
    require __DIR__ . '/vendor/ipd.php';
    require __DIR__ . '/vendor/consent.php';
    require __DIR__ . '/vendor/billing.php';
    require __DIR__ . '/vendor/patient.php';
});
