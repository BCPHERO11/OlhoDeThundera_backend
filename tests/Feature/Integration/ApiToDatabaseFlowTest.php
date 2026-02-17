<?php

namespace Tests\Feature\Integration;

use App\Enums\EnumCommandStatus;
use App\Enums\EnumCommandTypes;
use App\Enums\EnumDispatchStatus;
use App\Enums\EnumOccurrenceStatus;
use App\Models\Command;
use App\Models\Dispatch;
use App\Models\Occurrence;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
use Tests\TestCase;

class ApiToDatabaseFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.integration.api_key' => 'test-api-key']);
    }

    public function test_api_externa_persiste_comando_processado_e_ocorrencia_no_banco(): void
    {
        Redis::shouldReceive('set')->once()->andReturn(true);

        $externalId = (string) Str::uuid();

        $response = $this->postJson('/api/integrations/occurrences', [
            'externalId' => $externalId,
            'description' => 'Incêndio em residência',
            'type' => 'incendio_urbano',
            'reportedAt' => now()->toIso8601String(),
        ], [
            'X-API-Key' => 'test-api-key',
            'Idempotency-Key' => 'e2e-create-001',
        ]);

        $response->assertAccepted();

        $occurrence = Occurrence::where('external_id', $externalId)->first();

        $this->assertNotNull($occurrence);
        $this->assertSame(EnumOccurrenceStatus::REPORTED, $occurrence->status);

        $command = Command::where('type', EnumCommandTypes::OCCURRENCE_CREATED)
            ->where('source', 'sistema_externo')
            ->first();

        $this->assertNotNull($command);
        $this->assertSame(EnumCommandStatus::PROCESSED, $command->status);
        $this->assertNull($command->error);
        $this->assertSame($externalId, $command->payload['externalId']);
    }

    public function test_fluxo_api_para_banco_atualiza_ocorrencia_e_dispatch_do_inicio_ate_resolucao(): void
    {
        Redis::shouldReceive('set')->times(5)->andReturn(true);

        $externalId = (string) Str::uuid();

        $this->postJson('/api/integrations/occurrences', [
            'externalId' => $externalId,
            'description' => 'Ocorrência com ciclo completo',
            'type' => 'incendio_urbano',
            'reportedAt' => now()->toIso8601String(),
        ], [
            'X-API-Key' => 'test-api-key',
            'Idempotency-Key' => 'e2e-full-create-001',
        ])->assertAccepted();

        $occurrence = Occurrence::where('external_id', $externalId)->firstOrFail();

        $this->postJson("/api/occurrences/{$occurrence->id}/dispatches", [
            'resourceCode' => 'ABT-77',
        ], [
            'X-API-Key' => 'test-api-key',
            'Idempotency-Key' => 'e2e-full-dispatch-001',
        ])->assertAccepted();

        $dispatch = Dispatch::where('occurrence_id', $occurrence->id)->firstOrFail();

        $this->postJson("/api/occurrences/{$occurrence->id}/arrived", [
            'dispatchId' => $dispatch->id,
        ], [
            'X-API-Key' => 'test-api-key',
            'Idempotency-Key' => 'e2e-full-arrived-001',
        ])->assertAccepted();

        $this->postJson("/api/occurrences/{$occurrence->id}/start", [], [
            'X-API-Key' => 'test-api-key',
            'Idempotency-Key' => 'e2e-full-start-001',
        ])->assertAccepted();

        $this->postJson("/api/occurrences/{$occurrence->id}/resolve", [], [
            'X-API-Key' => 'test-api-key',
            'Idempotency-Key' => 'e2e-full-resolve-001',
        ])->assertAccepted();

        $this->assertSame(EnumOccurrenceStatus::RESOLVED, $occurrence->fresh()->status);
        $this->assertSame(EnumDispatchStatus::CLOSED, $dispatch->fresh()->status);

        $this->assertSame(1, Command::where('type', EnumCommandTypes::OCCURRENCE_CREATED)->count());
        $this->assertSame(1, Command::where('type', EnumCommandTypes::DISPATCH_ASSIGNED)->count());
        $this->assertSame(1, Command::where('type', EnumCommandTypes::DISPATCH_ON_SITE)->count());
        $this->assertSame(1, Command::where('type', EnumCommandTypes::OCCURRENCE_IN_PROGRESS)->count());
        $this->assertSame(1, Command::where('type', EnumCommandTypes::OCCURRENCE_RESOLVED)->count());

        $this->assertSame(5, Command::where('status', EnumCommandStatus::PROCESSED)->count());
        $this->assertSame(0, Command::where('status', EnumCommandStatus::FAILED)->count());
    }
}
