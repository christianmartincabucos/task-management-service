<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ExportController;
use App\Http\Controllers\Api\TranslationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Translation Management Service API
|
*/

// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Protected routes (require Sanctum token)
Route::middleware('auth:sanctum')->group(function (): void {
    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);

    // Translations CRUD
    Route::apiResource('translations', TranslationController::class);

    // JSON Export
    Route::get('/export/{locale?}', ExportController::class)->name('translations.export');
});
