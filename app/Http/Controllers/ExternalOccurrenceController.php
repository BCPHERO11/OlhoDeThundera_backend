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
        $validated = $request->validated();

        $commandPayload = [
            'id' => (string) Str::uuid(),
            'idempotency_key' => null,
            'source' => 'sistema_externo',
            'type' => EnumCommandTypes::OCCURRENCE_CREATED,
            'payload' => $validated,
            'status' => EnumCommandStatus::PENDING,
            'processed_at' => null,
            'error' => null,
        ];

        $key = $request->header('Idempotency-Key')
            . $commandPayload['type']->name()
            . $validated['externalId'];

        $commandPayload['idempotency_key'] = $key;

        $result = Redis::set($key, now()->toDateTimeString(), 'NX', 'EX', 60 * 60);

        if (!$result) {
            return response()->json([
                'message' => 'Cadastro já solicitado'
            ], 409);
        }

        ProcessApiPost::dispatch($commandPayload);

        return response()->json([
            'message' => 'Ocorrência recebida e colocada na fila'
        ], 202);
    }
}
