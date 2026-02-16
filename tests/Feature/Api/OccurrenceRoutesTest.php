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

    public function test_external_occurrence_route_dispatches_camel_case_command_payload(): void
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

        $response->assertAccepted();

        Queue::assertPushed(ProcessApiPost::class, function (ProcessApiPost $job) use ($externalId) {
            return isset($job->payload['id'])
                && $job->payload['type'] === EnumCommandTypes::OCCURRENCE_CREATED
                && $job->payload['payload']['externalId'] === $externalId
                && $job->payload['payload']['type'] === 'incendio_urbano'
                && isset($job->payload['idempotency_key']);
        });
    }

    public function test_internal_start_route_dispatches_camel_case_command_payload(): void
    {
        Queue::fake();
        Redis::shouldReceive('set')->once()->andReturn(true);

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

        $response = $this->postJson("/api/occurrences/{$occurrenceId}/start", [
            'startedAt' => now()->toIso8601String(),
        ], [
            'X-API-Key' => 'test-api-key',
            'Idempotency-Key' => 'internal-key-001',
        ]);

        $response->assertAccepted();

        Queue::assertPushed(ProcessApiPost::class, function (ProcessApiPost $job) use ($occurrenceId) {
            return $job->payload['type'] === EnumCommandTypes::OCCURRENCE_IN_PROGRESS
                && $job->payload['payload']['occurrenceId'] === $occurrenceId
                && isset($job->payload['idempotency_key']);
        });
    }


    public function test_internal_create_route_dispatches_occurrence_created_command(): void
    {
        Queue::fake();
        Redis::shouldReceive('set')->once()->andReturn(true);

        $externalId = (string) Str::uuid();

        $response = $this->postJson('/api/occurrences/create', [
            'externalId' => $externalId,
            'description' => 'Teste interno',
            'type' => 'incendio_urbano',
            'reportedAt' => now()->toIso8601String(),
        ], [
            'X-API-Key' => 'test-api-key',
            'Idempotency-Key' => 'internal-create-key-001',
        ]);

        $response->assertAccepted()
            ->assertJson([
                'message' => 'Ocorrência recebida e colocada na fila',
            ]);

        Queue::assertPushed(ProcessApiPost::class, function (ProcessApiPost $job) use ($externalId) {
            return $job->payload['type'] === EnumCommandTypes::OCCURRENCE_CREATED
                && $job->payload['source'] === 'sistema_interno'
                && $job->payload['payload']['externalId'] === $externalId;
        });
    }

    public function test_internal_arrived_route_dispatches_dispatch_on_site_command_payload(): void
    {
        Queue::fake();
        Redis::shouldReceive('set')->once()->andReturn(true);

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
            'Idempotency-Key' => 'internal-arrived-key-001',
        ]);

        $response->assertAccepted();

        Queue::assertPushed(ProcessApiPost::class, function (ProcessApiPost $job) use ($occurrenceId, $dispatchId) {
            return $job->payload['type'] === EnumCommandTypes::DISPATCH_ON_SITE
                && $job->payload['payload']['occurrenceId'] === $occurrenceId
                && $job->payload['payload']['dispatchId'] === $dispatchId;
        });
    }

    public function test_internal_cancel_route_dispatches_occurrence_cancelled_command_payload(): void
    {
        Queue::fake();
        Redis::shouldReceive('set')->once()->andReturn(true);

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

        $response = $this->postJson("/api/occurrences/{$occurrenceId}/cancel", [], [
            'X-API-Key' => 'test-api-key',
            'Idempotency-Key' => 'internal-cancel-key-001',
        ]);

        $response->assertAccepted();

        Queue::assertPushed(ProcessApiPost::class, function (ProcessApiPost $job) use ($occurrenceId) {
            return $job->payload['type'] === EnumCommandTypes::OCCURRENCE_CANCELLED
                && $job->payload['payload']['occurrenceId'] === $occurrenceId;
        });
    }

    public function test_internal_occurrence_list_route_returns_filtered_data(): void
    {
        DB::table('occurrences')->insert([
            [
                'id' => (string) Str::uuid(),
                'external_id' => (string) Str::uuid(),
                'type' => 'incendio_urbano',
                'status' => 1,
                'description' => 'Ocorrência em andamento de incêndio urbano',
                'reported_at' => now()->subHour(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => (string) Str::uuid(),
                'external_id' => (string) Str::uuid(),
                'type' => 'deslizamento',
                'status' => 1,
                'description' => 'Ocorrência em andamento de deslizamento',
                'reported_at' => now()->subMinutes(30),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => (string) Str::uuid(),
                'external_id' => (string) Str::uuid(),
                'type' => 'incendio_urbano',
                'status' => 0,
                'description' => 'Ocorrência reportada de incêndio urbano',
                'reported_at' => now()->subMinutes(10),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $response = $this->getJson('/api/occurrences/in_progress/incendio_urbano', [
            'X-API-Key' => 'test-api-key',
            'Idempotency-Key' => 'internal-list-key-001',
        ]);

        $response->assertOk()
            ->assertJsonPath('filters.status', 'in_progress')
            ->assertJsonPath('filters.type', 'incendio_urbano')
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.type', 'incendio_urbano')
            ->assertJsonPath('data.0.status', 'in_progress');
    }

    public function test_internal_occurrence_list_route_validates_status_filter(): void
    {
        $response = $this->getJson('/api/occurrences/invalid_status', [
            'X-API-Key' => 'test-api-key',
            'Idempotency-Key' => 'internal-list-key-002',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('message', 'Os dados fornecidos são inválidos.')
            ->assertJsonStructure([
                'errors' => ['status'],
            ]);
    }
}
