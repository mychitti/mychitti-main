<?php

use Illuminate\Support\Facades\Route;
use App\Modules\School\Controllers\Vendor\DashboardController;

/* 
 | School module — vendor routes.
 | Mounted inside the vendor auth group (namespace Vendor, name prefix `vendor.`,
 | middleware [vendor, module.resolve, ...]) from routes/vendor.php.
 | Everything here is gated by the `school_manage` plan.
 */
Route::group(['middleware' => ['planwise:school_manage']], function () {
    Route::group(['prefix' => 'school', 'as' => 'school.'], function () { 

        Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard')->middleware('permission:school_dashboard,view');

        require __DIR__ . '/vendor/academic.php';
        require __DIR__ . '/vendor/students.php'; 
        require __DIR__ . '/vendor/student-attendance.php';
        require __DIR__ . '/vendor/student-leave.php';
        require __DIR__ . '/vendor/short-leave.php';
        require __DIR__ . '/vendor/fees.php';
        require __DIR__ . '/vendor/exams.php';
        require __DIR__ . '/vendor/question-bank.php';
        require __DIR__ . '/vendor/certificates.php';
        require __DIR__ . '/vendor/timetable.php';
        require __DIR__ . '/vendor/admissions.php';
        require __DIR__ . '/vendor/branch.php';
        require __DIR__ . '/vendor/settings.php';
        require __DIR__ . '/vendor/promotion.php';
        require __DIR__ . '/vendor/reports.php';
        require __DIR__ . '/vendor/notices.php';
        require __DIR__ . '/vendor/lookups.php';
        require __DIR__ . '/vendor/homework.php';
        require __DIR__ . '/vendor/transport.php';
        require __DIR__ . '/vendor/hostel.php';
    });
});
