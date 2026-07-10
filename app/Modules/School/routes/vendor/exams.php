<?php

use Illuminate\Support\Facades\Route;
use App\Modules\School\Controllers\Vendor\ExamController;

Route::group(['prefix' => 'exams', 'as' => 'exams.'], function () {
    Route::get('/',                          [ExamController::class, 'index'])->name('index')->middleware('permission:exams,view,exams,add,exams,enter_marks');
    Route::get('create',                     [ExamController::class, 'create'])->name('create')->middleware('permission:exams,add');
    Route::post('store',                     [ExamController::class, 'store'])->name('store')->middleware('permission:exams,add');
    Route::get('{exam}',                     [ExamController::class, 'show'])->name('show')->middleware('permission:exams,view');

    Route::post('{exam}/subject',            [ExamController::class, 'subject_store'])->name('subject.store')->middleware('permission:exams,edit');
    Route::get('{exam}/subject/delete/{id}', [ExamController::class, 'subject_delete'])->name('subject.delete')->middleware('permission:exams,edit');

    Route::get('{exam}/marks',               [ExamController::class, 'marks'])->name('marks')->middleware('permission:exams,enter_marks');
    Route::post('{exam}/marks',              [ExamController::class, 'marks_store'])->name('marks.store')->middleware('permission:exams,enter_marks');

    Route::get('{exam}/report-cards',        [ExamController::class, 'reportCards'])->name('report-cards')->middleware('permission:exams,view');
    Route::get('{exam}/report-card/{student}', [ExamController::class, 'reportCard'])->name('report-card')->middleware('permission:exams,view');
    Route::get('{exam}/notify-results',      [ExamController::class, 'notifyResults'])->name('notify-results')->middleware('permission:exams,edit');
});
 