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

class FluxoComandosOcorrenciaIntegracaoTest extends TestCase
{
    use RefreshDatabase;

    public function test_fluxo_principal_cria_dispatch_inicia_chega_e_resolve_com_estados_esperados(): void
    {
        $externalId = (string) Str::uuid();

        $this->executarComando(
            EnumCommandTypes::OCCURRENCE_CREATED,
            [
                'externalId' => $externalId,
                'description' => 'Incêndio residencial',
                'type' => 'incendio_urbano',
                'reportedAt' => now()->toIso8601String(),
            ],
            'fluxo-criacao'
        );

        $occurrence = Occurrence::where('external_id', $externalId)->firstOrFail();

        $this->assertSame(
            [EnumOccurrenceStatus::REPORTED->value],
            [$occurrence->status->value]
        );

        $this->executarComando(
            EnumCommandTypes::DISPATCH_ASSIGNED,
            [
                'occurrenceId' => $occurrence->id,
                'resourceCode' => 'ABT-01',
            ],
            'fluxo-dispatch-assigned'
        );

        $dispatch = Dispatch::where('occurrence_id', $occurrence->id)->firstOrFail();

        $this->assertSame(
            [EnumOccurrenceStatus::REPORTED->value, EnumDispatchStatus::ASSIGNED->value],
            [$occurrence->fresh()->status->value, $dispatch->status->value]
        );

        $dispatch->update(['status' => EnumDispatchStatus::EN_ROUTE]);

        $this->executarComando(
            EnumCommandTypes::OCCURRENCE_IN_PROGRESS,
            ['occurrenceId' => $occurrence->id],
            'fluxo-ocorrencia-iniciada'
        );

        $this->assertSame(
            [EnumOccurrenceStatus::IN_PROGRESS->value, EnumDispatchStatus::EN_ROUTE->value],
            [$occurrence->fresh()->status->value, $dispatch->fresh()->status->value]
        );

        $this->executarComando(
            EnumCommandTypes::DISPATCH_ON_SITE,
            [
                'occurrenceId' => $occurrence->id,
                'dispatchId' => $dispatch->id,
            ],
            'fluxo-dispatch-chegada'
        );

        $this->assertSame(
            [EnumOccurrenceStatus::IN_PROGRESS->value, EnumDispatchStatus::ON_SITE->value],
            [$occurrence->fresh()->status->value, $dispatch->fresh()->status->value]
        );

        $this->executarComando(
            EnumCommandTypes::OCCURRENCE_RESOLVED,
            ['occurrenceId' => $occurrence->id],
            'fluxo-ocorrencia-resolvida'
        );

        $this->assertSame(
            [EnumOccurrenceStatus::RESOLVED->value, EnumDispatchStatus::CLOSED->value],
            [$occurrence->fresh()->status->value, $dispatch->fresh()->status->value]
        );
    }

    public function test_inicio_da_ocorrencia_sem_dispatch_marca_comando_como_falho(): void
    {
        $occurrence = Occurrence::create([
            'external_id' => (string) Str::uuid(),
            'type' => 'incendio_urbano',
            'status' => EnumOccurrenceStatus::REPORTED,
            'description' => 'Ocorrência sem despacho',
            'reported_at' => now(),
        ]);

        $command = Command::create([
            'idempotency_key' => 'inicio-sem-dispatch',
            'source' => 'teste-integracao',
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
            $this->assertStringContainsString('veículo no local', (string) $command->error);
        }
    }

    public function test_adicionar_dispatch_em_ocorrencia_cancelada_ou_resolvida_marca_comando_como_falho(): void
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

        $this->assertaFalhaAoAtribuirDispatch($cancelledOccurrence->id, 'dispatch-cancelada', 'Ocorrência já cancelada');
        $this->assertaFalhaAoAtribuirDispatch($resolvedOccurrence->id, 'dispatch-resolvida', 'finalizada');
    }

    private function executarComando(EnumCommandTypes $type, array $payload, string $idempotencyKey): Command
    {
        $command = Command::create([
            'idempotency_key' => $idempotencyKey,
            'source' => 'teste-integracao',
            'type' => $type,
            'payload' => $payload,
            'status' => EnumCommandStatus::PENDING,
        ]);

        app(CommandHandler::class)->handle($command);

        return $command->fresh();
    }

    private function assertaFalhaAoAtribuirDispatch(string $occurrenceId, string $idempotencyKey, string $trechoMensagem): void
    {
        $command = Command::create([
            'idempotency_key' => $idempotencyKey,
            'source' => 'teste-integracao',
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
            $this->assertStringContainsString($trechoMensagem, $exception->getMessage());
            $this->assertStringContainsString($trechoMensagem, (string) $command->error);
        }
    }
}
