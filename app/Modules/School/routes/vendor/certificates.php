<?php

use Illuminate\Support\Facades\Route;
use App\Modules\School\Controllers\Vendor\CertificateController;

Route::group(['prefix' => 'certificates', 'as' => 'certificates.'], function () {
    Route::get('/',            [CertificateController::class, 'index'])->name('index')->middleware('permission:certificates,view,certificates,add,certificates,edit');

    Route::get('settings',     [CertificateController::class, 'settings'])->name('settings')->middleware('permission:certificates,edit');
    Route::post('settings',    [CertificateController::class, 'save_settings'])->name('settings.save')->middleware('permission:certificates,edit');

    Route::get('students/search', [CertificateController::class, 'search'])->name('students.search')->middleware('permission:certificates,add');
    Route::get('issue',        [CertificateController::class, 'create'])->name('create')->middleware('permission:certificates,add');
    Route::post('issue',       [CertificateController::class, 'store'])->name('store')->middleware('permission:certificates,add');

    Route::get('{id}',         [CertificateController::class, 'show'])->name('show')->middleware('permission:certificates,view');
    Route::get('{id}/pdf',     [CertificateController::class, 'pdf'])->name('pdf')->middleware('permission:certificates,view');
    Route::get('{id}/delete',  [CertificateController::class, 'delete'])->name('delete')->middleware('permission:certificates,delete');
});
