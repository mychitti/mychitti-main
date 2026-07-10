<?php

use Illuminate\Support\Facades\Route;
use App\Modules\School\Controllers\Vendor\StudentController;
use App\Modules\School\Controllers\Vendor\StudentImportController;

Route::group(['prefix' => 'students', 'as' => 'students.'], function () {
    Route::get('/',              [StudentController::class, 'index'])->name('index')->middleware('permission:students,view,students,add,students,edit');
    Route::get('settings',       [StudentController::class, 'settings'])->name('settings')->middleware('permission:school_settings,edit');
    Route::post('settings',      [StudentController::class, 'save_settings'])->name('settings.save')->middleware('permission:school_settings,edit');
    Route::get('import',         [StudentImportController::class, 'index'])->name('import')->middleware('permission:students,import');
    Route::get('import/template',[StudentImportController::class, 'template'])->name('import.template')->middleware('permission:students,import');
    Route::post('import',        [StudentImportController::class, 'import'])->name('import.store')->middleware('permission:students,import');
    Route::get('create',         [StudentController::class, 'create'])->name('create')->middleware('permission:students,add');
    Route::post('store',         [StudentController::class, 'store'])->name('store')->middleware('permission:students,add');
    Route::get('{id}',           [StudentController::class, 'show'])->name('show')->middleware('permission:students,view');
    Route::get('{id}/edit',      [StudentController::class, 'edit'])->name('edit')->middleware('permission:students,edit');
    Route::post('{id}/update',   [StudentController::class, 'update'])->name('update')->middleware('permission:students,edit');
    Route::get('{id}/id-card',   [StudentController::class, 'idCard'])->name('id-card')->middleware('permission:students,id_card'); 
    Route::get('{id}/dashboard', [StudentController::class, 'dashboard'])->name('dashboard')->middleware('permission:students,view');
    Route::post('{id}/documents', [StudentController::class, 'document_store'])->name('documents.store')->middleware('permission:students,edit');
    Route::get('{id}/documents/{docId}/delete', [StudentController::class, 'document_delete'])->name('documents.delete')->middleware('permission:students,edit');
    Route::get('{id}/delete',    [StudentController::class, 'delete'])->name('delete')->middleware('permission:students,delete');
});
