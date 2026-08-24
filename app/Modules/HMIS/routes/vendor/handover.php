<?php

use App\Modules\HMIS\Controllers\Vendor\HmisHandoverController;

// Chain of custody at the counter — who took work away, who brought a report back, and what
// proves they were really from the lab they said they were.
//
// Its own file rather than a block inside opd.php or lab.php because it belongs to neither: the
// same form records a ceramics runner collecting impressions and a pathology courier delivering
// reports. {type} names which, and the controller maps it to the permission that governs it, so
// there is no per-feature middleware here — a single gate could only be right for one of them.
Route::group(['prefix' => 'handover', 'as' => 'handover.'], function () {
    // {type} is pinned to the two subjects that exist. Without it 'handover/12/verify' would match
    // the two-segment store route below as type=12, id=verify — the id space and the subject slugs
    // share a position, and only the constraint keeps them apart.
    Route::get('{type}/{id}/start', [HmisHandoverController::class, 'start'])
        ->where('type', 'opd_lab_work|lab_order')->name('start');
    Route::post('{type}/{id}/otp', [HmisHandoverController::class, 'otp'])
        ->where('type', 'opd_lab_work|lab_order')->name('otp');
    Route::post('{type}/{id}', [HmisHandoverController::class, 'store'])
        ->where('type', 'opd_lab_work|lab_order')->name('store');

    Route::post('{handover}/verify',  [HmisHandoverController::class, 'verify'])->name('verify')->whereNumber('handover');
    Route::post('{handover}/confirm', [HmisHandoverController::class, 'confirm'])->name('confirm')->whereNumber('handover');
    Route::get('{handover}/slip',     [HmisHandoverController::class, 'slip'])->name('slip')->whereNumber('handover');
});
