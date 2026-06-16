<?php

use Illuminate\Support\Facades\Route;
use App\Modules\School\Controllers\Vendor\BranchController;

Route::group(['prefix' => 'branches', 'as' => 'branches.'], function () {
    Route::get('/',            [BranchController::class, 'index'])->name('index');
    Route::post('store',       [BranchController::class, 'store'])->name('store');
    Route::get('delete/{id}',  [BranchController::class, 'delete'])->name('delete');
    Route::get('switch/{id}',  [BranchController::class, 'switch'])->name('switch');
});
