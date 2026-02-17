<?php

namespace Tests\Unit\Jobs;

use App\Domain\Command\CommandHandler;
use App\Enums\EnumCommandStatus;
use App\Enums\EnumCommandTypes;
use App\Enums\EnumOccurrenceStatus;
use App\Jobs\ProcessApiPost;
use App\Models\Command;
use App\Models\Occurrence;
use App\Repositories\CommandRepository;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ProcessApiPostCommandFailureTest extends TestCase
{
    use RefreshDatabase;

    public function test_persiste_comando_falho_quando_dispatch_quebra_fluxo_em_ocorrencia_cancelada(): void
    {
        $occurrence = Occurrence::create([
            'external_id' => (string) Str::uuid(),
            'type' => 'incendio_urbano',
            'status' => EnumOccurrenceStatus::CANCELLED,
            'description' => 'Ocorrência cancelada',
            'reported_at' => now(),
        ]);

        $payload = [
            'idempotency_key' => 'dispatch-cancelled-occurrence-key',
            'source' => 'sistema_interno',
            'type' => EnumCommandTypes::DISPATCH_ASSIGNED,
            'payload' => [
                'occurrenceId' => $occurrence->id,
                'resourceCode' => 'ABT-01',
            ],
            'status' => EnumCommandStatus::PENDING,
            'processed_at' => null,
            'error' => null,
        ];

        $job = new ProcessApiPost($payload);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Ocorrência já cancelada');

        try {
            $job->handle(app(CommandHandler::class), app(CommandRepository::class));
        } finally {
            $command = Command::where('idempotency_key', $payload['idempotency_key'])->first();

            $this->assertNotNull($command);
            $this->assertSame(EnumCommandStatus::FAILED, $command->status);
            $this->assertSame('Erro por quebra de fluxo: Ocorrência já cancelada', $command->error);
        }
    }

    public function test_persiste_comando_falho_quando_ocorrencia_quebra_fluxo_por_nao_ter_dispatch(): void
    {
        $occurrence = Occurrence::create([
            'external_id' => (string) Str::uuid(),
            'type' => 'incendio_urbano',
            'status' => EnumOccurrenceStatus::REPORTED,
            'description' => 'Ocorrência sem dispatch',
            'reported_at' => now(),
        ]);

        $payload = [
            'idempotency_key' => 'occurrence-start-without-dispatch-key',
            'source' => 'sistema_interno',
            'type' => EnumCommandTypes::OCCURRENCE_IN_PROGRESS,
            'payload' => [
                'occurrenceId' => $occurrence->id,
            ],
            'status' => EnumCommandStatus::PENDING,
            'processed_at' => null,
            'error' => null,
        ];

        $job = new ProcessApiPost($payload);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Só é possível iniciar ocorrência após ao menos um veículo no local.');

        try {
            $job->handle(app(CommandHandler::class), app(CommandRepository::class));
        } finally {
            $command = Command::where('idempotency_key', $payload['idempotency_key'])->first();

            $this->assertNotNull($command);
            $this->assertSame(EnumCommandStatus::FAILED, $command->status);
            $this->assertStringContainsString('Erro por quebra de fluxo', (string) $command->error);
            $this->assertStringContainsString('veículo no local', (string) $command->error);
        }
    }
}
