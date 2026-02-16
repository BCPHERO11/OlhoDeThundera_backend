<?php

namespace Tests\Feature\Integration;

use App\Domain\Command\CommandHandler;
use App\Enums\EnumCommandStatus;
use App\Enums\EnumCommandTypes;
use App\Enums\EnumDispatchStatus;
use App\Enums\EnumOccurrenceStatus;
use App\Models\Command;
use App\Models\Dispatch;
use App\Models\Occurrence;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class OccurrenceCommandFlowIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_common_flow_create_dispatch_start_arrive_and_resolve_with_expected_states(): void
    {
        $externalId = (string) Str::uuid();

        $this->handleCommand(
            EnumCommandTypes::OCCURRENCE_CREATED,
            [
                'externalId' => $externalId,
                'description' => 'Incêndio residencial',
                'type' => 'incendio_urbano',
                'reportedAt' => now()->toIso8601String(),
            ],
            'flow-created'
        );

        $occurrence = Occurrence::where('external_id', $externalId)->firstOrFail();

        $this->assertSame(
            [EnumOccurrenceStatus::REPORTED->value],
            [$occurrence->status->value]
        );

        $this->handleCommand(
            EnumCommandTypes::DISPATCH_ASSIGNED,
            [
                'occurrenceId' => $occurrence->id,
                'resourceCode' => 'ABT-01',
            ],
            'flow-dispatch-assigned'
        );

        $dispatch = Dispatch::where('occurrence_id', $occurrence->id)->firstOrFail();

        $this->assertSame(
            [EnumOccurrenceStatus::REPORTED->value, EnumDispatchStatus::ASSIGNED->value],
            [$occurrence->fresh()->status->value, $dispatch->status->value]
        );

        $dispatch->update(['status' => EnumDispatchStatus::EN_ROUTE]);

        $this->handleCommand(
            EnumCommandTypes::OCCURRENCE_IN_PROGRESS,
            ['occurrenceId' => $occurrence->id],
            'flow-occurrence-started'
        );

        $this->assertSame(
            [EnumOccurrenceStatus::IN_PROGRESS->value, EnumDispatchStatus::EN_ROUTE->value],
            [$occurrence->fresh()->status->value, $dispatch->fresh()->status->value]
        );

        $this->handleCommand(
            EnumCommandTypes::DISPATCH_ON_SITE,
            [
                'occurrenceId' => $occurrence->id,
                'dispatchId' => $dispatch->id,
            ],
            'flow-dispatch-arrived'
        );

        $this->assertSame(
            [EnumOccurrenceStatus::IN_PROGRESS->value, EnumDispatchStatus::ON_SITE->value],
            [$occurrence->fresh()->status->value, $dispatch->fresh()->status->value]
        );

        $this->handleCommand(
            EnumCommandTypes::OCCURRENCE_RESOLVED,
            ['occurrenceId' => $occurrence->id],
            'flow-occurrence-resolved'
        );

        $this->assertSame(
            [EnumOccurrenceStatus::RESOLVED->value, EnumDispatchStatus::CLOSED->value],
            [$occurrence->fresh()->status->value, $dispatch->fresh()->status->value]
        );
    }

    public function test_starting_occurrence_without_dispatch_marks_command_as_failed(): void
    {
        $occurrence = Occurrence::create([
            'external_id' => (string) Str::uuid(),
            'type' => 'incendio_urbano',
            'status' => EnumOccurrenceStatus::REPORTED,
            'description' => 'Ocorrência sem despacho',
            'reported_at' => now(),
        ]);

        $command = Command::create([
            'idempotency_key' => 'start-without-dispatch',
            'source' => 'integration-test',
            'type' => EnumCommandTypes::OCCURRENCE_IN_PROGRESS,
            'payload' => ['occurrenceId' => $occurrence->id],
            'status' => EnumCommandStatus::PENDING,
        ]);

        $this->expectException(DomainException::class);

        try {
            app(CommandHandler::class)->handle($command);
        } finally {
            $command->refresh();

            $this->assertSame(EnumCommandStatus::FAILED, $command->status);
            $this->assertStringContainsString('Erro por quebra de fluxo', (string) $command->error);
            $this->assertStringContainsString('dispatch ON_SITE', (string) $command->error);
        }
    }

    public function test_adding_dispatch_to_cancelled_or_resolved_occurrence_marks_commands_as_failed(): void
    {
        $cancelledOccurrence = Occurrence::create([
            'external_id' => (string) Str::uuid(),
            'type' => 'incendio_urbano',
            'status' => EnumOccurrenceStatus::CANCELLED,
            'description' => 'Ocorrência cancelada',
            'reported_at' => now(),
        ]);

        $resolvedOccurrence = Occurrence::create([
            'external_id' => (string) Str::uuid(),
            'type' => 'incendio_urbano',
            'status' => EnumOccurrenceStatus::RESOLVED,
            'description' => 'Ocorrência resolvida',
            'reported_at' => now(),
        ]);

        $this->assertDispatchAssignmentFails($cancelledOccurrence->id, 'dispatch-cancelled', 'Ocorrência já cancelada');
        $this->assertDispatchAssignmentFails($resolvedOccurrence->id, 'dispatch-resolved', 'finalizada');
    }

    private function handleCommand(EnumCommandTypes $type, array $payload, string $idempotencyKey): Command
    {
        $command = Command::create([
            'idempotency_key' => $idempotencyKey,
            'source' => 'integration-test',
            'type' => $type,
            'payload' => $payload,
            'status' => EnumCommandStatus::PENDING,
        ]);

        app(CommandHandler::class)->handle($command);

        return $command->fresh();
    }

    private function assertDispatchAssignmentFails(string $occurrenceId, string $idempotencyKey, string $messagePart): void
    {
        $command = Command::create([
            'idempotency_key' => $idempotencyKey,
            'source' => 'integration-test',
            'type' => EnumCommandTypes::DISPATCH_ASSIGNED,
            'payload' => [
                'occurrenceId' => $occurrenceId,
                'resourceCode' => 'ABT-09',
            ],
            'status' => EnumCommandStatus::PENDING,
        ]);

        try {
            app(CommandHandler::class)->handle($command);
            $this->fail('Era esperado erro de domínio ao criar dispatch para ocorrência inválida.');
        } catch (DomainException $exception) {
            $command->refresh();

            $this->assertSame(EnumCommandStatus::FAILED, $command->status);
            $this->assertStringContainsString($messagePart, $exception->getMessage());
            $this->assertStringContainsString($messagePart, (string) $command->error);
        }
    }
}
