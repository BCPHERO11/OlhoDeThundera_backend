<?php

use App\Http\Controllers\ExternalOccurrenceController;
use App\Http\Controllers\InternalOccurrenceController;
use App\Http\Controllers\ViewItensController;

Route::middleware(['api', 'api.key', 'api.indempotency'])->group(function () {
    Route::prefix('integrations')->group(function () {
        // Rota que registra ocorrencias // Occurrence = 0 e sem dispatch
        Route::post('occurrences', [ExternalOccurrenceController::class, 'store']);
    });


    Route::get('occurrences/{status?}/{type?}', [ViewItensController::class, 'index']);

    Route::prefix('occurrences')->group(function () {

        // Rota que registra ocorrencias // Occurrence = 0 e sem dispatch Command = Occurrence_created
        Route::post('create', [InternalOccurrenceController::class, 'store']);

        //TODO verificar as novas rotas criadas
        Route::prefix('{uuid}')->whereUuid('uuid')->group(function () {
            // Rota que adiciona despacho Dispatch = 0 e Ocurrence 0 Command = Dispatch_assigned
            Route::post('dispatches', [InternalOccurrenceController::class, 'dispatch']);
            // Rota que inicia a ocorrencia Dispatch = 1 e Ocurrence 1 Command = Occurrence_in_progress
            Route::post('start', [InternalOccurrenceController::class, 'start']);
            // Rota que registra a chegada do despacho Dispatch = 2 e Occurrence 1 Command = Dispatch_on_site
            Route::post('arrived', [InternalOccurrenceController::class, 'arrived']);
            // Rota que finaliza a ocorrencia Dispatch = 3 e Ocurrence 2 Command = Ocurrence_resolved
            Route::post('resolve', [InternalOccurrenceController::class, 'resolve']);
            // Rota que cancela a ocorrencia Dispatch = 3 e Ocurrence 3 Command = Ocurrence_cancelled
            Route::post('cancel', [InternalOccurrenceController::class, 'cancel']);
        });
    });

    //Route::post('occurrences', [])
});
