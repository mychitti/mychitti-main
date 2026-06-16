<?php

use Illuminate\Support\Facades\Route;
use App\Modules\School\Controllers\Vendor\LookupController;

Route::group(['prefix' => 'lookup', 'as' => 'lookup.'], function () {
    Route::get('teachers', [LookupController::class, 'teachers'])->name('teachers');
    Route::get('students', [LookupController::class, 'students'])->name('students');
});
