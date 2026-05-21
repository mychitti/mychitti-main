<?php

use App\Modules\POS\Controllers\Vendor\BranchController;

Route::group(['prefix' => 'branch', 'as' => 'branch.'], function () {
    Route::get('/',           [BranchController::class, 'index'])->name('index');
    Route::post('store',      [BranchController::class, 'store'])->name('store')->middleware('permission:pos_branch,add');
    Route::get('delete/{id}', [BranchController::class, 'delete'])->name('delete')->middleware('permission:pos_branch,delete');
    Route::post('update',     [BranchController::class, 'update'])->name('update')->middleware('permission:pos_branch,edit');
});
