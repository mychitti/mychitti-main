<?php

use Illuminate\Support\Facades\Route;
use App\Modules\School\Controllers\Vendor\SettingsController;

Route::group(['prefix' => 'settings', 'as' => 'settings.'], function () {
    Route::get('/',  [SettingsController::class, 'index'])->name('index')->middleware('permission:school_settings,view,school_settings,edit');
    Route::post('/', [SettingsController::class, 'save'])->name('save')->middleware('permission:school_settings,edit');
    Route::post('template', [SettingsController::class, 'saveTemplate'])->name('template')->middleware('permission:school_settings,edit');
    Route::get('notification-preferences', [SettingsController::class, 'notificationPreferences'])->name('notification-preferences')->middleware('permission:school_settings,view,school_settings,edit');
    Route::post('notification-preferences', [SettingsController::class, 'saveNotificationPreferences'])->name('notification-preferences.save')->middleware('permission:school_settings,edit');
});
  