<?php

use Illuminate\Support\Facades\Route;
use App\Modules\School\Controllers\Vendor\TimetableController;

Route::group(['prefix' => 'timetable', 'as' => 'timetable.'], function () {
    Route::get('/',                  [TimetableController::class, 'index'])->name('index')->middleware('permission:timetable,view,timetable,add,timetable,edit');
    Route::post('save',              [TimetableController::class, 'save'])->name('save')->middleware('permission:timetable,add');

    Route::get('periods',            [TimetableController::class, 'periods'])->name('periods')->middleware('permission:timetable,edit');
    Route::post('periods',           [TimetableController::class, 'period_save'])->name('periods.save')->middleware('permission:timetable,edit');
    Route::get('periods/delete/{id}',[TimetableController::class, 'period_delete'])->name('periods.delete')->middleware('permission:timetable,edit');

    Route::get('view',               [TimetableController::class, 'show'])->name('show')->middleware('permission:timetable,view');
    Route::get('pdf',                [TimetableController::class, 'pdf'])->name('pdf')->middleware('permission:timetable,view');

    Route::get('teacher',            [TimetableController::class, 'teacher'])->name('teacher')->middleware('permission:timetable,view');

    Route::get('substitutions',          [TimetableController::class, 'substitutions'])->name('substitutions')->middleware('permission:timetable,view');
    Route::post('substitutions',         [TimetableController::class, 'substitution_save'])->name('substitutions.save')->middleware('permission:timetable,add');
    Route::get('substitutions/delete/{id}', [TimetableController::class, 'substitution_delete'])->name('substitutions.delete')->middleware('permission:timetable,delete');
});
