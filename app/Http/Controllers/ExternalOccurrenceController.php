<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreExternalOccurrenceRequest;
use App\Jobs\ProcessApiPost;
use Illuminate\Http\JsonResponse;
use App\Enums\EnumCommandStatus;
use App\Enums\EnumCommandTypes;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;

class ExternalOccurrenceController extends Controller
{
    public function store(StoreExternalOccurrenceRequest $request): JsonResponse
    {
        // dados validados
        $validated = $request->validated();

        $commandPayload = array(
            'id' => (string) Str::uuid(),
            'indempotency_key' => $request->header('Idempotency-Key'),
            'source' => 'sistema_externo',
            'type' => EnumCommandTypes::OCCURRENCE_CREATED,
            'payload' => $validated,
            'status' => EnumCommandStatus::PENDING,
            'processed_at' => null,
            'error' => null
        );

        $key = $commandPayload['indempotency_key'] . $commandPayload['type']->name() . $validated['externalId'];

        // Regra que vai garantir a indepotência do Redis na Fila impidindo repetião por 1 minuto
        $result = Redis::set($key, now()->toDateTimeString(),'NX', 'EX', 60 * 60);

        if (!$result) {
            return response()->json([
                'message' => 'Cadastro já solicitado'
            ], 409);
        }

        // enviar para fila
        ProcessApiPost::dispatch($commandPayload);

        return response()->json([
            "message" => "Ocorrência recebida e colocada na fila"
        ], 202);
    }
}
