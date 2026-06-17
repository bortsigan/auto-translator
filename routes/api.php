<?php

declare(strict_types=1);

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\LanguageController;
use App\Http\Controllers\Api\TagController;
use App\Http\Controllers\Api\TranslationController;
use App\Http\Controllers\Api\TranslationExportController;
use Illuminate\Support\Facades\Route;

// Public endpoints
Route::post('auth/register', [AuthController::class, 'register']);
Route::post('auth/login', [AuthController::class, 'login']);

Route::get('translations/export/{locale}', TranslationExportController::class)
    ->whereAlphaNumeric('locale')
    ->name('translations.export');

Route::middleware('auth.token')->group(function (): void {
    Route::post('auth/logout', [AuthController::class, 'logout']);

    Route::get('languages', [LanguageController::class, 'index']);
    Route::post('languages', [LanguageController::class, 'store']);

    Route::get('tags', [TagController::class, 'index']);
    Route::post('tags', [TagController::class, 'store']);

    Route::get('translations', [TranslationController::class, 'index']);
    Route::post('translations', [TranslationController::class, 'store']);
    Route::get('translations/{translation}', [TranslationController::class, 'show']);
    Route::put('translations/{translation}', [TranslationController::class, 'update']);
    Route::patch('translations/{translation}', [TranslationController::class, 'update']);
    Route::delete('translations/{translation}', [TranslationController::class, 'destroy']);
});
