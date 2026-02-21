<?php

use App\Http\Controllers\Api\AIChatController;
use Illuminate\Support\Facades\Route;

/*
|------------------------------------------------------------- -------------
| AI Service API Routes
|--------------------------------------------------------------------------
| Protected by ApiKeyMiddleware (X-Api-Key header).
| Register ApiKeyMiddleware in app/Http/Kernel.php:
|   'ai.key' => \App\Http\Middleware\ApiKeyMiddleware::class,
|
| Add AI_SERVICE_KEY=<secret> to .env on this server.
*/

Route::middleware('ai.key')->prefix('ai')->group(function () {
    Route::post('chat',    [AIChatController::class, 'chat']);
    Route::get('history',  [AIChatController::class, 'history']);
    Route::post('clear',   [AIChatController::class, 'clearMemory']);
});
