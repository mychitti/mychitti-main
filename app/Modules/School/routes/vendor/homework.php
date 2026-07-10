<?php

use Illuminate\Support\Facades\Route;
use App\Modules\School\Controllers\Vendor\HomeworkController;

Route::group(['prefix' => 'homework', 'as' => 'homework.'], function ()  {
    Route::get('/',             [HomeworkController::class, 'index'])->name('index')->middleware('permission:homework,view,homework,add,homework,edit,homework,evaluate');
    Route::get('create',        [HomeworkController::class, 'create'])->name('create')->middleware('permission:homework,add');
    Route::post('store',        [HomeworkController::class, 'store'])->name('store')->middleware('permission:homework,add');
    Route::get('{id}/edit',     [HomeworkController::class, 'edit'])->name('edit')->middleware('permission:homework,edit');
    Route::post('{id}/update',  [HomeworkController::class, 'update'])->name('update')->middleware('permission:homework,edit');
    Route::get('{id}/delete',   [HomeworkController::class, 'delete'])->name('delete')->middleware('permission:homework,delete');
    Route::get('{id}/submissions', [HomeworkController::class, 'submissions'])->name('submissions')->middleware('permission:homework,view,homework,evaluate');
    Route::post('{id}/evaluate', [HomeworkController::class, 'evaluate'])->name('evaluate')->middleware('permission:homework,evaluate');
});
 