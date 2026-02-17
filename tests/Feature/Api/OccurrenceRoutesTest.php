<?php

namespace Tests\Feature\Api;

use App\Enums\EnumCommandTypes;
use App\Jobs\ProcessApiPost;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
use Tests\TestCase;

class OccurrenceRoutesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.integration.api_key' => 'test-api-key']);
    }

    public function test_rota_externa_de_ocorrencia_envia_comando_com_payload_esperado(): void
    {
        Queue::fake();
        Redis::shouldReceive('set')->once()->andReturn(true);

        $externalId = (string) Str::uuid();

        $response = $this->postJson('/api/integrations/occurrences', [
            'externalId' => $externalId,
            'description' => 'Teste integração',
            'type' => 'incendio_urbano',
            'reportedAt' => now()->toIso8601String(),
        ], [
            'X-API-Key' => 'test-api-key',
            'Idempotency-Key' => 'external-key-001',
        ]);

        $response->assertAccepted()
            ->assertJsonPath('message', 'Ocorrência recebida e colocada na fila');

        Queue::assertPushed(ProcessApiPost::class, function (ProcessApiPost $job) use ($externalId) {
            return isset($job->payload['id'])
                && $job->payload['type'] === EnumCommandTypes::OCCURRENCE_CREATED
                && $job->payload['payload']['externalId'] === $externalId
                && $job->payload['source'] === 'sistema_externo';
        });
    }

    public function test_rota_de_dispatch_envia_comando_de_despacho_atribuido(): void
    {
        Queue::fake();
        Redis::shouldReceive('set')->once()->andReturn(true);

        $occurrenceId = $this->criarOcorrencia();

        $response = $this->postJson("/api/occurrences/{$occurrenceId}/dispatches", [
            'resourceCode' => 'ABT-99',
        ], [
            'X-API-Key' => 'test-api-key',
            'Idempotency-Key' => 'dispatch-key-001',
        ]);

        $response->assertAccepted()
            ->assertJsonPath('message', 'Solicitação de despacho recebida e colocada na fila');

        Queue::assertPushed(ProcessApiPost::class, function (ProcessApiPost $job) use ($occurrenceId) {
            return $job->payload['type'] === EnumCommandTypes::DISPATCH_ASSIGNED
                && $job->payload['payload']['occurrenceId'] === $occurrenceId
                && $job->payload['payload']['resourceCode'] === 'ABT-99'
                && $job->idempotencyKey === 'dispatch-key-001DISPATCH_ASSIGNED' . $occurrenceId;
        });
    }

    public function test_rota_de_inicio_envia_comando_de_ocorrencia_em_andamento(): void
    {
        Queue::fake();
        Redis::shouldReceive('set')->once()->andReturn(true);

        $occurrenceId = $this->criarOcorrencia();

        $response = $this->postJson("/api/occurrences/{$occurrenceId}/start", [], [
            'X-API-Key' => 'test-api-key',
            'Idempotency-Key' => 'start-key-001',
        ]);

        $response->assertAccepted()
            ->assertJsonPath('message', 'Solicitação de início da ocorrência recebida e colocada na fila');

        Queue::assertPushed(ProcessApiPost::class, function (ProcessApiPost $job) use ($occurrenceId) {
            return $job->payload['type'] === EnumCommandTypes::OCCURRENCE_IN_PROGRESS
                && $job->payload['payload']['occurrenceId'] === $occurrenceId;
        });
    }

    public function test_rota_de_chegada_envia_comando_de_dispatch_no_local(): void
    {
        Queue::fake();
        Redis::shouldReceive('set')->once()->andReturn(true);

        $occurrenceId = $this->criarOcorrencia();
        $dispatchId = (string) Str::uuid();

        DB::table('dispatches')->insert([
            'id' => $dispatchId,
            'occurrence_id' => $occurrenceId,
            'resource_code' => 'ABT-99',
            'status' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->postJson("/api/occurrences/{$occurrenceId}/arrived", [
            'dispatchId' => $dispatchId,
        ], [
            'X-API-Key' => 'test-api-key',
            'Idempotency-Key' => 'arrived-key-001',
        ]);

        $response->assertAccepted()
            ->assertJsonPath('message', 'Solicitação de chegada do despacho recebida e colocada na fila');

        Queue::assertPushed(ProcessApiPost::class, function (ProcessApiPost $job) use ($occurrenceId, $dispatchId) {
            return $job->payload['type'] === EnumCommandTypes::DISPATCH_ON_SITE
                && $job->payload['payload']['occurrenceId'] === $occurrenceId
                && $job->payload['payload']['dispatchId'] === $dispatchId;
        });
    }

    public function test_rota_de_resolucao_envia_comando_de_ocorrencia_resolvida(): void
    {
        Queue::fake();
        Redis::shouldReceive('set')->once()->andReturn(true);

        $occurrenceId = $this->criarOcorrencia();

        $response = $this->postJson("/api/occurrences/{$occurrenceId}/resolve", [], [
            'X-API-Key' => 'test-api-key',
            'Idempotency-Key' => 'resolve-key-001',
        ]);

        $response->assertAccepted()
            ->assertJsonPath('message', 'Solicitação de resolução da ocorrência recebida e colocada na fila');

        Queue::assertPushed(ProcessApiPost::class, function (ProcessApiPost $job) use ($occurrenceId) {
            return $job->payload['type'] === EnumCommandTypes::OCCURRENCE_RESOLVED
                && $job->payload['payload']['occurrenceId'] === $occurrenceId;
        });
    }

    public function test_rota_de_cancelamento_envia_comando_de_ocorrencia_cancelada(): void
    {
        Queue::fake();
        Redis::shouldReceive('set')->once()->andReturn(true);

        $occurrenceId = $this->criarOcorrencia();

        $response = $this->postJson("/api/occurrences/{$occurrenceId}/cancel", [], [
            'X-API-Key' => 'test-api-key',
            'Idempotency-Key' => 'cancel-key-001',
        ]);

        $response->assertAccepted()
            ->assertJsonPath('message', 'Solicitação de cancelamento da ocorrência recebida e colocada na fila');

        Queue::assertPushed(ProcessApiPost::class, function (ProcessApiPost $job) use ($occurrenceId) {
            return $job->payload['type'] === EnumCommandTypes::OCCURRENCE_CANCELLED
                && $job->payload['payload']['occurrenceId'] === $occurrenceId;
        });
    }

    public function test_rota_retorna_conflito_quando_chave_de_idempotencia_ja_foi_usada(): void
    {
        Queue::fake();
        Redis::shouldReceive('set')->once()->andReturn(false);

        $occurrenceId = $this->criarOcorrencia();

        $response = $this->postJson("/api/occurrences/{$occurrenceId}/start", [], [
            'X-API-Key' => 'test-api-key',
            'Idempotency-Key' => 'duplicada-001',
        ]);

        $response->assertStatus(409)
            ->assertJsonPath('message', 'Solicitação já recebida para iniciar ocorrência');

        Queue::assertNothingPushed();
    }

    public function test_rota_retorna_mensagem_de_validacao_em_portugues_para_dispatch_invalido(): void
    {
        $occurrenceId = $this->criarOcorrencia();

        $response = $this->postJson("/api/occurrences/{$occurrenceId}/dispatches", [], [
            'X-API-Key' => 'test-api-key',
            'Idempotency-Key' => 'dispatch-invalido-001',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('message', 'Os dados fornecidos são inválidos.')
            ->assertJsonPath('errors.resourceCode.0', 'O código do recurso despachado é obrigatório.');
    }

    private function criarOcorrencia(): string
    {
        $occurrenceId = (string) Str::uuid();

        DB::table('occurrences')->insert([
            'id' => $occurrenceId,
            'external_id' => (string) Str::uuid(),
            'type' => 'incendio_urbano',
            'status' => 0,
            'description' => 'Ocorrência existente',
            'reported_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $occurrenceId;
    }
}
