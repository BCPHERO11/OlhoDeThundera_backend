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

    public function test_it_persists_failed_command_when_dispatch_breaks_flow_for_cancelled_occurrence(): void
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
            $job->handle(
                app(CommandHandler::class),
                app(CommandRepository::class)
            );
        } finally {
            $command = Command::where('idempotency_key', $payload['idempotency_key'])->first();

            $this->assertNotNull($command);
            $this->assertSame(EnumCommandStatus::FAILED, $command->status);
            $this->assertSame(
                'Erro por quebra de fluxo: Ocorrência já cancelada',
                $command->error
            );
        }
    }
}
