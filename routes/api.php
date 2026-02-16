<?php

use App\Http\Controllers\ExternalOccurrenceController;
use App\Http\Controllers\InternalOccurrenceController;

Route::middleware(['api', 'api.key', 'api.indempotency'])->group(function () {
    Route::prefix('integrations')->group(function () {
        Route::post('occurrences', [ExternalOccurrenceController::class, 'store']);
    });

    Route::prefix('occurrences/{uuid}')->whereUuid('uuid')->group(function () {
        Route::post('start', [InternalOccurrenceController::class, 'start']);
        Route::post('resolve', [InternalOccurrenceController::class, 'resolve']);
        Route::post('dispatches', [InternalOccurrenceController::class, 'dispatch']);
    });
});
