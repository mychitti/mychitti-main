<?php

use Illuminate\Support\Facades\Route;
use App\Modules\School\Controllers\Vendor\TransportController;

Route::group(['prefix' => 'transport', 'as' => 'transport.'], function () {
    Route::get('/',                     [TransportController::class, 'index'])->name('index')->middleware('permission:transport,view,transport,add,transport,edit');
    
    // Routes 
    Route::post('route/store',          [TransportController::class, 'routeStore'])->name('route.store')->middleware('permission:transport,add');
    Route::get('route/{id}/delete',     [TransportController::class, 'routeDelete'])->name('route.delete')->middleware('permission:transport,delete');
    
    // Vehicles 
    Route::post('vehicle/store',        [TransportController::class, 'vehicleStore'])->name('vehicle.store')->middleware('permission:transport,add');
    Route::get('vehicle/{id}/delete',   [TransportController::class, 'vehicleDelete'])->name('vehicle.delete')->middleware('permission:transport,delete');
    
    // Stops
    Route::post('stop/store',           [TransportController::class, 'stopStore'])->name('stop.store')->middleware('permission:transport,add');
    Route::get('stop/{id}/delete',       [TransportController::class, 'stopDelete'])->name('stop.delete')->middleware('permission:transport,delete');
    
    // Allocations
    Route::post('allocation/store',     [TransportController::class, 'allocationStore'])->name('allocation.store')->middleware('permission:transport,add');
    Route::get('allocation/{id}/delete',[TransportController::class, 'allocationDelete'])->name('allocation.delete')->middleware('permission:transport,delete');
});
