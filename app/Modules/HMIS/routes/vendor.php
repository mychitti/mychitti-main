<?php

use Illuminate\Support\Facades\Route;

// All doctor routes are free — no hospital_manage plan required
require __DIR__ . '/vendor/doctor.php';
// Basic Pharmacy is free too (shares inventory_items with the premium Inventory module)
require __DIR__ . '/vendor/pharmacy.php';

Route::group(['middleware' => ['planwise:hospital_manage']], function () {
    require __DIR__ . '/vendor/hospital.php';
    require __DIR__ . '/vendor/appointment.php';
    require __DIR__ . '/vendor/prescription.php';
    require __DIR__ . '/vendor/nurse.php';
    require __DIR__ . '/vendor/ward.php';
    require __DIR__ . '/vendor/opd.php';
    require __DIR__ . '/vendor/ipd.php';
    require __DIR__ . '/vendor/consent.php';
    require __DIR__ . '/vendor/billing.php';
    require __DIR__ . '/vendor/patient.php';
    require __DIR__ . '/vendor/lab.php';
    require __DIR__ . '/vendor/nursing.php';
    require __DIR__ . '/vendor/preop.php';
    require __DIR__ . '/vendor/radiology.php';
    require __DIR__ . '/vendor/whatsapp.php';
});
