<?php

use App\Modules\HMIS\Controllers\Vendor\ConsentController;

Route::group(['prefix' => 'consent', 'as' => 'consent.'], function () {
    Route::group(['prefix' => 'template', 'as' => 'template.'], function () {
        Route::get('',           [ConsentController::class, 'templateIndex'])->name('index');
        Route::get('create',     [ConsentController::class, 'templateCreate'])->name('create')->middleware('permission:consent_template,add');
        Route::post('store',     [ConsentController::class, 'templateStore'])->name('store')->middleware('permission:consent_template,add');
        Route::get('{id}/edit',  [ConsentController::class, 'templateEdit'])->name('edit')->middleware('permission:consent_template,edit');
        Route::put('{id}',       [ConsentController::class, 'templateUpdate'])->name('update')->middleware('permission:consent_template,edit');
        Route::delete('{id}',    [ConsentController::class, 'templateDestroy'])->name('destroy')->middleware('permission:consent_template,delete');
    });
    Route::get('',                           [ConsentController::class, 'index'])->name('index');
    Route::get('create',                     [ConsentController::class, 'create'])->name('create')->middleware('permission:consent_form,add');
    Route::post('store',                     [ConsentController::class, 'store'])->name('store')->middleware('permission:consent_form,add');
    Route::get('{id}',                       [ConsentController::class, 'show'])->name('show')->middleware('permission:consent_form,view');
    Route::delete('{id}',                    [ConsentController::class, 'destroy'])->name('destroy')->middleware('permission:consent_form,delete');
    Route::get('template-content/{id}',      [ConsentController::class, 'templateContent'])->name('template-content')->middleware('permission:consent_template,view');
});
