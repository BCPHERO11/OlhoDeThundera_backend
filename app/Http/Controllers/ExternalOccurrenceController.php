<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreExternalOccurrenceRequest;
use App\Jobs\ProcessApiPost;
use Illuminate\Http\JsonResponse;

class ExternalOccurrenceController extends Controller
{
    public function store(StoreExternalOccurrenceRequest $request): JsonResponse
    {
        // dados validados
        $validated = $request->validated();

        // montar o payload para o comando/fila
        $commandPayload = [
            "type"            => "command",
            "occurence_data"  => $validated,
            "received_at"     => now()->toDateTimeString(),
            "idempotency_key" => $request->header("Idempotency-Key"),
            "api_key"         => $request->header("X-Api-Key"),
        ];

        // enviar para fila
        ProcessApiPost::dispatch($commandPayload);

        return response()->json([
            "status"  => "queued",
            "message" => "Ocorrência recebida e colocada na fila"
        ], 202);
    }
}
