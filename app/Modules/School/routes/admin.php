<?php

use Illuminate\Support\Facades\Route;

/*
 | School module — admin routes.
 | Mounted from routes/admin.php. Will hold plan tiers (student-count tiers)
 | and activity logs, mirroring the HMIS admin routes.
 */
Route::prefix('school')->name('school.')->group(function () {
    // Route::prefix('plan')->group(function () { ... });
    // Route::prefix('logs')->group(function () { ... });
});
