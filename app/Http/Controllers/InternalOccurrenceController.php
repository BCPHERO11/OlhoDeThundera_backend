<?php

namespace App\Http\Controllers;

use App\Enums\EnumCommandStatus;
use App\Enums\EnumCommandTypes;
use App\Http\Requests\ArrivedOccurrenceRequest;
use App\Http\Requests\CancelOccurrenceRequest;
use App\Http\Requests\CreateOccurrenceRequest;
use App\Http\Requests\DispatchOccurrenceRequest;
use App\Http\Requests\ResolveOccurrenceRequest;
use App\Http\Requests\StartOccurrenceRequest;
use App\Jobs\ProcessApiPost;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;

class InternalOccurrenceController extends Controller
{
    public function create(CreateOccurrenceRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $commandPayload = [
            'id' => (string) Str::uuid(),
            'idempotency_key' => null,
            'source' => 'sistema_interno',
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
                'message' => 'Solicitação já recebida para criação da ocorrência',
            ], 409);
        }

        ProcessApiPost::dispatch($commandPayload);

        return response()->json([
            'message' => 'Solicitação de criação da ocorrência recebida e colocada na fila',
        ], 202);
    }

    public function start(StartOccurrenceRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $validated['assignedAt'] = now()->toDateTimeString();

        $commandPayload = [
            'id' => (string) Str::uuid(),
            'idempotency_key' => null,
            'source' => 'sistema_interno',
            'type' => EnumCommandTypes::OCCURRENCE_IN_PROGRESS,
            'payload' => $validated,
            'status' => EnumCommandStatus::PENDING,
            'processed_at' => null,
            'error' => null,
        ];

        $key = $request->header('Idempotency-Key')
            . $commandPayload['type']->name()
            . $validated['occurrenceId'];

        $commandPayload['idempotency_key'] = $key;

        $result = Redis::set($key, now()->toDateTimeString(), 'NX', 'EX', 60 * 60);

        if (!$result) {
            return response()->json([
                'message' => 'Solicitação já recebida para iniciar ocorrência',
            ], 409);
        }

        ProcessApiPost::dispatch($commandPayload);

        return response()->json([
            'message' => 'Solicitação de início da ocorrência recebida e colocada na fila',
        ], 202);
    }

    public function resolve(ResolveOccurrenceRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $validated['assignedAt'] = now()->toDateTimeString();

        $commandPayload = [
            'id' => (string) Str::uuid(),
            'idempotency_key' => null,
            'source' => 'sistema_interno',
            'type' => EnumCommandTypes::OCCURRENCE_RESOLVED,
            'payload' => $validated,
            'status' => EnumCommandStatus::PENDING,
            'processed_at' => null,
            'error' => null,
        ];

        $key = $request->header('Idempotency-Key')
            . $commandPayload['type']->name()
            . $validated['occurrenceId'];

        $commandPayload['idempotency_key'] = $key;

        $result = Redis::set($key, now()->toDateTimeString(), 'NX', 'EX', 60 * 60);

        if (!$result) {
            return response()->json([
                'message' => 'Solicitação já recebida para resolução da ocorrência',
            ], 409);
        }

        ProcessApiPost::dispatch($commandPayload);

        return response()->json([
            'message' => 'Solicitação de resolução da ocorrência recebida e colocada na fila',
        ], 202);
    }

    public function dispatch(DispatchOccurrenceRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $validated['assignedAt'] = now()->toDateTimeString();

        $commandPayload = [
            'id' => (string) Str::uuid(),
            'idempotency_key' => null,
            'source' => 'sistema_interno',
            'type' => EnumCommandTypes::DISPATCH_ASSIGNED,
            'payload' => $validated,
            'status' => EnumCommandStatus::PENDING,
            'processed_at' => null,
            'error' => null,
        ];

        $key = $request->header('Idempotency-Key')
            . $commandPayload['type']->name()
            . $validated['occurrenceId']
            . $validated['resourceCode'];

        $commandPayload['idempotency_key'] = $key;

        $result = Redis::set($key, now()->toDateTimeString(), 'NX', 'EX', 60 * 60);

        if (!$result) {
            return response()->json([
                'message' => 'Solicitação já recebida para despacho da ocorrência',
            ], 409);
        }

        ProcessApiPost::dispatch($commandPayload);

        return response()->json([
            'message' => 'Solicitação de despacho recebida e colocada na fila',
        ], 202);
    }

    public function arrived(ArrivedOccurrenceRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $validated['assignedAt'] = now()->toDateTimeString();

        $commandPayload = [
            'id' => (string) Str::uuid(),
            'idempotency_key' => null,
            'source' => 'sistema_interno',
            'type' => EnumCommandTypes::DISPATCH_ON_SITE,
            'payload' => $validated,
            'status' => EnumCommandStatus::PENDING,
            'processed_at' => null,
            'error' => null,
        ];

        $key = $request->header('Idempotency-Key')
            . $commandPayload['type']->name()
            . $validated['occurrenceId']
            . $validated['dispatchId'];

        $commandPayload['idempotency_key'] = $key;

        $result = Redis::set($key, now()->toDateTimeString(), 'NX', 'EX', 60 * 60);

        if (!$result) {
            return response()->json([
                'message' => 'Solicitação já recebida para chegada do despacho',
            ], 409);
        }

        ProcessApiPost::dispatch($commandPayload);

        return response()->json([
            'message' => 'Solicitação de chegada do despacho recebida e colocada na fila',
        ], 202);
    }

    public function cancel(CancelOccurrenceRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $validated['assignedAt'] = now()->toDateTimeString();

        $commandPayload = [
            'id' => (string) Str::uuid(),
            'idempotency_key' => null,
            'source' => 'sistema_interno',
            'type' => EnumCommandTypes::OCCURRENCE_CANCELLED,
            'payload' => $validated,
            'status' => EnumCommandStatus::PENDING,
            'processed_at' => null,
            'error' => null,
        ];

        $key = $request->header('Idempotency-Key')
            . $commandPayload['type']->name()
            . $validated['occurrenceId'];

        $commandPayload['idempotency_key'] = $key;

        $result = Redis::set($key, now()->toDateTimeString(), 'NX', 'EX', 60 * 60);

        if (!$result) {
            return response()->json([
                'message' => 'Solicitação já recebida para cancelamento da ocorrência',
            ], 409);
        }

        ProcessApiPost::dispatch($commandPayload);

        return response()->json([
            'message' => 'Solicitação de cancelamento da ocorrência recebida e colocada na fila',
        ], 202);
    }
}
