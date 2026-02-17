<?php

namespace Tests\Unit\Repositories;

use App\Enums\EnumCommandStatus;
use App\Enums\EnumCommandTypes;
use App\Repositories\CommandRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommandRepositoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_cria_comando_pendente(): void
    {
        $repository = app(CommandRepository::class);

        $command = $repository->create([
            'idempotency_key' => 'cmd-create-001',
            'source' => 'teste',
            'type' => EnumCommandTypes::OCCURRENCE_CREATED,
            'payload' => ['externalId' => 'abc'],
            'status' => EnumCommandStatus::PENDING,
        ]);

        $this->assertSame(EnumCommandStatus::PENDING, $command->status);
    }

    public function test_mark_as_processed_atualiza_status_e_data(): void
    {
        $repository = app(CommandRepository::class);

        $command = $repository->create([
            'idempotency_key' => 'cmd-processed-001',
            'source' => 'teste',
            'type' => EnumCommandTypes::OCCURRENCE_CREATED,
            'payload' => ['externalId' => 'def'],
            'status' => EnumCommandStatus::PENDING,
        ]);

        $repository->markAsProcessed($command);

        $this->assertSame(EnumCommandStatus::PROCESSED, $command->fresh()->status);
        $this->assertNotNull($command->fresh()->processed_at);
    }

    public function test_mark_as_failed_atualiza_status_erro_e_data(): void
    {
        $repository = app(CommandRepository::class);

        $command = $repository->create([
            'idempotency_key' => 'cmd-failed-001',
            'source' => 'teste',
            'type' => EnumCommandTypes::OCCURRENCE_CREATED,
            'payload' => ['externalId' => 'ghi'],
            'status' => EnumCommandStatus::PENDING,
        ]);

        $repository->markAsFailed($command, 'Erro de teste');

        $this->assertSame(EnumCommandStatus::FAILED, $command->fresh()->status);
        $this->assertSame('Erro de teste', $command->fresh()->error);
        $this->assertNotNull($command->fresh()->processed_at);
    }
}
