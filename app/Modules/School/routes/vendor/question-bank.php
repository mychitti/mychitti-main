<?php

use Illuminate\Support\Facades\Route;
use App\Modules\School\Controllers\Vendor\QuestionBankController;

Route::group(['prefix' => 'question-bank', 'as' => 'question-bank.'], function () {
    Route::get('/',           [QuestionBankController::class, 'index'])->name('index')->middleware('permission:question_bank,view,question_bank,add');
    Route::post('save',       [QuestionBankController::class, 'store'])->name('save')->middleware('permission:question_bank,add');
    Route::get('delete/{id}', [QuestionBankController::class, 'delete'])->name('delete')->middleware('permission:question_bank,delete');
    Route::get('paper',       [QuestionBankController::class, 'paper'])->name('paper')->middleware('permission:question_bank,view');
});
