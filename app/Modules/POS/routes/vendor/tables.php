<?php

use App\Modules\POS\Controllers\Vendor\RestaurantTableController;

Route::get('restaurant-tables',           [RestaurantTableController::class, 'index'])->name('restaurant-tables.index')->middleware('permission:restaurant_tables,list');
Route::get('restaurant-tables/create',    [RestaurantTableController::class, 'create'])->name('restaurant-tables.create')->middleware('permission:restaurant_tables,create');
Route::post('restaurant-tables',          [RestaurantTableController::class, 'store'])->name('restaurant-tables.store')->middleware('permission:restaurant_tables,create');
Route::get('restaurant-tables/{id}/edit', [RestaurantTableController::class, 'edit'])->name('restaurant-tables.edit')->middleware('permission:restaurant_tables,edit');
Route::put('restaurant-tables/{id}',      [RestaurantTableController::class, 'update'])->name('restaurant-tables.update')->middleware('permission:restaurant_tables,edit');
Route::delete('restaurant-tables/{id}',   [RestaurantTableController::class, 'destroy'])->name('restaurant-tables.destroy')->middleware('permission:restaurant_tables,delete');
