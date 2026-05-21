<?php

use App\Modules\HMIS\Controllers\Admin\HospitalActivityLogController;
use App\Modules\HMIS\Controllers\Admin\HospitalBedTierController;
use Illuminate\Support\Facades\Route;

Route::prefix('logs')->group(function () {
    Route::get('hospital-activity-logs', [HospitalActivityLogController::class, 'index'])->name('logs.hospital-activity-logs');
});

Route::prefix('plan')->group(function () {
    Route::get('hospital-bed-tiers',          [HospitalBedTierController::class, 'index'])->name('plan.hospital-bed-tiers');
    Route::post('hospital-bed-tiers',         [HospitalBedTierController::class, 'store'])->name('plan.hospital-bed-tiers.store');
    Route::post('hospital-bed-tiers/{id}',    [HospitalBedTierController::class, 'update'])->name('plan.hospital-bed-tiers.update');
    Route::delete('hospital-bed-tiers/{id}',  [HospitalBedTierController::class, 'destroy'])->name('plan.hospital-bed-tiers.destroy');
});
