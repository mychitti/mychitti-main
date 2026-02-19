<?php

use App\Http\Controllers\PromptController;
use Illuminate\Support\Facades\Route;

// Prompt Manager
Route::prefix('prompts')->name('prompts.')->group(function () {
    Route::get('/',                                  [PromptController::class, 'index'])     ->name('index');
    Route::post('/',                                 [PromptController::class, 'store'])     ->name('store');
    Route::put('/{prompt}',                          [PromptController::class, 'update'])    ->name('update');
    Route::post('/{prompt}/duplicate',               [PromptController::class, 'duplicate']) ->name('duplicate');
    Route::post('/{prompt}/restore/{version}',       [PromptController::class, 'restore'])   ->name('restore');
    Route::delete('/{prompt}',                       [PromptController::class, 'destroy'])   ->name('destroy');
});
 