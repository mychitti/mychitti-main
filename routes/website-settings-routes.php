<?php

// Add these routes to your routes/vendor.php or web.php file
// Make sure they are within the vendor middleware group

use App\Http\Controllers\Vendor\WebsiteSettingsController;

Route::group(['prefix' => 'settings', 'as' => 'settings.', 'middleware' => ['vendor']], function () {
    
    // Website Settings Main Page
    Route::get('/website', [WebsiteSettingsController::class, 'index'])->name('website');
    
    // Domain Configuration
    Route::post('/update-domain', [WebsiteSettingsController::class, 'updateDomain'])->name('update-domain');
    Route::post('/check-domain', [WebsiteSettingsController::class, 'checkDomainAvailability'])->name('check-domain');
    Route::get('/dns-instructions', [WebsiteSettingsController::class, 'getDnsInstructions'])->name('dns-instructions');
    
    // Layout Settings
    Route::post('/update-layout', [WebsiteSettingsController::class, 'updateLayout'])->name('update-layout');
    
    // Contact Information
    Route::post('/webpage-update', [WebsiteSettingsController::class, 'webpageUpdate'])->name('webpage-update');
    
    // Location
    Route::post('/update-location', [WebsiteSettingsController::class, 'updateLocation'])->name('update-location');
    
    // Branding
    Route::post('/update-branding', [WebsiteSettingsController::class, 'updateBranding'])->name('update-branding');
});