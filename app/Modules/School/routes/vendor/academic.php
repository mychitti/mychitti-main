<?php

use Illuminate\Support\Facades\Route;
use App\Modules\School\Controllers\Vendor\AcademicController;

Route::group(['prefix' => 'academic', 'as' => 'academic.'], function () {
    Route::get('/', [AcademicController::class, 'index'])->name('index')->middleware('permission:academic_setup,view,academic_setup,add,academic_setup,edit');

    Route::post('session',            [AcademicController::class, 'session_store'])->name('session.store')->middleware('permission:academic_setup,add');
    Route::get('session/delete/{id}', [AcademicController::class, 'session_delete'])->name('session.delete')->middleware('permission:academic_setup,delete');

    Route::post('class',            [AcademicController::class, 'class_store'])->name('class.store')->middleware('permission:academic_setup,add');
    Route::get('class/delete/{id}', [AcademicController::class, 'class_delete'])->name('class.delete')->middleware('permission:academic_setup,delete');

    Route::post('section',            [AcademicController::class, 'section_store'])->name('section.store')->middleware('permission:academic_setup,add');
    Route::get('section/delete/{id}', [AcademicController::class, 'section_delete'])->name('section.delete')->middleware('permission:academic_setup,delete');

    Route::post('subject',            [AcademicController::class, 'subject_store'])->name('subject.store')->middleware('permission:academic_setup,add');
    Route::get('subject/delete/{id}', [AcademicController::class, 'subject_delete'])->name('subject.delete')->middleware('permission:academic_setup,delete');

    Route::post('mapping',            [AcademicController::class, 'mapping_store'])->name('mapping.store')->middleware('permission:academic_setup,add');
    Route::get('mapping/delete/{id}', [AcademicController::class, 'mapping_delete'])->name('mapping.delete')->middleware('permission:academic_setup,delete');
});
