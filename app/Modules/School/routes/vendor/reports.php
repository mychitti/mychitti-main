<?php

use Illuminate\Support\Facades\Route;
use App\Modules\School\Controllers\Vendor\ReportController;

Route::group(['prefix' => 'reports', 'as' => 'reports.'], function () {
    Route::get('/', [ReportController::class, 'index'])->name('index')->middleware('permission:school_reports,view,school_reports,export');
    Route::get('export', [ReportController::class, 'export'])->name('export')->middleware('permission:school_reports,export');
});
 