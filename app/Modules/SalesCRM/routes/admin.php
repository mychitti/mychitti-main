<?php

use App\Modules\SalesCRM\Controllers\Admin\FollowUpController;
use App\Modules\SalesCRM\Controllers\Admin\PipelineController;
use App\Modules\SalesCRM\Controllers\Admin\QueryController;
use App\Modules\SalesCRM\Controllers\Admin\ReportController;
use App\Modules\SalesCRM\Controllers\Admin\TicketController;
use App\Modules\SalesCRM\Controllers\Admin\ZoneAccessController;
use Illuminate\Support\Facades\Route;

Route::group([
    'prefix' => 'sales-crm',
    'as'     => 'sales-crm.',
], function () {

    Route::get('pipeline',                          [PipelineController::class,   'index'])->name('pipeline');
    Route::get('reports/zone',                      [ReportController::class,     'zone'])->name('report.zone');
    Route::get('zone-access',                       [ZoneAccessController::class, 'index'])->name('zone-access.index');
    Route::post('zone-access/{admin}',              [ZoneAccessController::class, 'update'])->name('zone-access.update');

    Route::prefix('queries')->name('query.')->group(function () {
        Route::get('/',           [QueryController::class, 'index'])->name('index');
        Route::get('/create',     [QueryController::class, 'create'])->name('create');
        Route::post('/',          [QueryController::class, 'store'])->name('store');
        Route::get('/{id}',       [QueryController::class, 'show'])->name('show');
        Route::get('/{id}/edit',  [QueryController::class, 'edit'])->name('edit');
        Route::put('/{id}',       [QueryController::class, 'update'])->name('update');
        Route::delete('/{id}',    [QueryController::class, 'destroy'])->name('destroy');
        Route::post('/{id}/status',   [QueryController::class, 'updateStatus'])->name('status');
        Route::post('/{id}/activity', [QueryController::class, 'logActivity'])->name('activity');
    });

    Route::prefix('followups')->name('followup.')->group(function () {
        Route::get('/',                  [FollowUpController::class, 'index'])->name('index');
        Route::get('/my',                [FollowUpController::class, 'myHistory'])->name('my');
        Route::get('/create',            [FollowUpController::class, 'create'])->name('create');
        Route::post('/',                 [FollowUpController::class, 'store'])->name('store');
        Route::post('/{id}/status',      [FollowUpController::class, 'updateStatus'])->name('status');
        Route::post('/{id}/assign',      [FollowUpController::class, 'assign'])->name('assign');
        Route::get('/{id}/assignments',  [FollowUpController::class, 'assignmentHistory'])->name('assignments');
        Route::delete('/{id}',           [FollowUpController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('tickets')->name('ticket.')->group(function () {
        Route::get('/',              [TicketController::class, 'index'])->name('index');
        Route::get('/create',        [TicketController::class, 'create'])->name('create');
        Route::post('/',             [TicketController::class, 'store'])->name('store');
        Route::get('/{id}',          [TicketController::class, 'show'])->name('show');
        Route::get('/{id}/edit',     [TicketController::class, 'edit'])->name('edit');
        Route::put('/{id}',          [TicketController::class, 'update'])->name('update');
        Route::delete('/{id}',       [TicketController::class, 'destroy'])->name('destroy');
        Route::post('/{id}/status',  [TicketController::class, 'updateStatus'])->name('status');
        Route::post('/{id}/reply',   [TicketController::class, 'reply'])->name('reply');
    });
});
