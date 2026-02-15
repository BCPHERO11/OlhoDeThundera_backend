<?php

use App\Jobs\ProcessApiPost;
use Illuminate\Http\Request;
use App\Http\Controllers\ExternalOccurrenceController;

Route::middleware(['api.key', 'api.indempotency'])->group(function () {
    Route::prefix('integrations')->group(function () {
        Route::post('occurrences', [ExternalOccurrenceController::class, 'store']);
    });

    Route::prefix('occurrences')->group(function () {
        Route::post('start', [InternalOccurrenceController::class, 'start']);
        Route::post('resolve', [InternalOccurrenceController::class, 'resolve']);
        Route::post('dispatch', [InternalOccurrenceController::class, 'dispatch']);
    });
});

