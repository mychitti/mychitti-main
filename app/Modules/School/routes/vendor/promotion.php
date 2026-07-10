<?php

use Illuminate\Support\Facades\Route;
use App\Modules\School\Controllers\Vendor\PromotionController;

Route::group(['prefix' => 'promotion', 'as' => 'promotion.'], function () {
    Route::get('/',        [PromotionController::class, 'index'])->name('index')->middleware('permission:student_promotion,view,student_promotion,promote');
    Route::post('process', [PromotionController::class, 'process'])->name('process')->middleware('permission:student_promotion,promote');
});
 