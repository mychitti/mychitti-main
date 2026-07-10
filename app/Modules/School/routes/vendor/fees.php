<?php

use Illuminate\Support\Facades\Route;
use App\Modules\School\Controllers\Vendor\FeeController;
use App\Modules\School\Controllers\Vendor\ConcessionController;

Route::group(['prefix' => 'fees', 'as' => 'fees.'], function () {
    // Landing (dues) — openable by anyone holding any fee-related permission
    Route::get('/',                 [FeeController::class, 'index'])->name('index')->middleware('permission:fee_dues,view,fee_collection,view,fee_heads,view,fee_structure,view,scholarship,view');

    // Scholarships / concessions
    Route::get('concessions',                    [ConcessionController::class, 'index'])->name('concessions')->middleware('permission:scholarship,view');
    Route::post('concessions/save',              [ConcessionController::class, 'save'])->name('concessions.save')->middleware('permission:scholarship,add');
    Route::get('concessions/delete/{id}',        [ConcessionController::class, 'delete'])->name('concessions.delete')->middleware('permission:scholarship,delete');
    Route::post('concessions/assign',            [ConcessionController::class, 'assign'])->name('concessions.assign')->middleware('permission:scholarship,add');
    Route::get('concessions/assign/delete/{id}', [ConcessionController::class, 'assignDelete'])->name('concessions.assign.delete')->middleware('permission:scholarship,delete');

    // Fee heads
    Route::get('heads',             [FeeController::class, 'heads'])->name('heads')->middleware('permission:fee_heads,view');
    Route::post('heads',            [FeeController::class, 'head_store'])->name('heads.store')->middleware('permission:fee_heads,add');
    Route::get('heads/delete/{id}', [FeeController::class, 'head_delete'])->name('heads.delete')->middleware('permission:fee_heads,delete');

    // Fee structure
    Route::get('structure',         [FeeController::class, 'structure'])->name('structure')->middleware('permission:fee_structure,view');
    Route::post('structure',        [FeeController::class, 'structure_save'])->name('structure.save')->middleware('permission:fee_structure,add');

    // Collection
    Route::get('collect/{student}', [FeeController::class, 'collect'])->name('collect')->middleware('permission:fee_collection,collect');
    Route::post('collect/{student}',[FeeController::class, 'collect_store'])->name('collect.store')->middleware('permission:fee_collection,collect');

    Route::get('receipt/{id}',      [FeeController::class, 'receipt'])->name('receipt')->middleware('permission:fee_collection,view');
    Route::get('receipt/{id}/pdf',  [FeeController::class, 'receipt_pdf'])->name('receipt.pdf')->middleware('permission:fee_collection,view');
    Route::get('reminder/{invoice}',[FeeController::class, 'sendReminder'])->name('reminder')->middleware('permission:fee_collection,view');

    Route::get('payments',          [FeeController::class, 'payments'])->name('payments')->middleware('permission:fee_collection,view');
});
