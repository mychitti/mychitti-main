<?php

use App\Modules\POS\Controllers\Vendor\SalespointController;

Route::get('settings', 'SettingsController@pos')->name('settings')->middleware('permission:pos,settings');
Route::group(['prefix' => 'setting', 'as' => 'setting.'], function () {
    Route::post('save', [SalespointController::class, 'setting_save'])->name('save')->middleware('permission:pos,settings');
});
