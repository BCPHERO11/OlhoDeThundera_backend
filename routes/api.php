<?php

use App\Jobs\ProcessApiPost;
use Illuminate\Http\Request;
use App\Http\Controllers\ExternalOccurrenceController;

Route::middleware('api.key')->prefix('integrations')->group(function () {
    Route::post('external-occurrences', [ExternalOccurrenceController::class, 'store']);
});

