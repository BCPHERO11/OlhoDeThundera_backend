<?php

namespace Tests\Unit\Services;

use App\Enums\EnumDispatchStatus;
use App\Enums\EnumOccurrenceStatus;
use App\Models\Dispatch;
use App\Models\Occurrence;
use App\Services\OccurrenceService;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class OccurrenceServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_cria_ocorrencia_com_status_reportado(): void
    {
        $occurrence = app(OccurrenceService::class)->create([
            'externalId' => (string) Str::uuid(),
            'type' => 'incendio_urbano',
            'description' => 'Ocorrência de teste',
            'reportedAt' => now()->toIso8601String(),
        ]);

        $this->assertSame(EnumOccurrenceStatus::REPORTED, $occurrence->status);
        $this->assertDatabaseHas('occurrences', ['id' => $occurrence->id]);
    }

    public function test_altera_status_da_ocorrencia_quando_fluxo_e_valido(): void
    {
        $occurrence = $this->criarOcorrencia(EnumOccurrenceStatus::REPORTED);

        Dispatch::create([
            'occurrence_id' => $occurrence->id,
            'resource_code' => 'ABT-20',
            'status' => EnumDispatchStatus::ON_SITE,
        ]);

        $atualizada = app(OccurrenceService::class)->changeStatusById(
            $occurrence->id,
            EnumOccurrenceStatus::IN_PROGRESS
        );

        $this->assertSame(EnumOccurrenceStatus::IN_PROGRESS, $atualizada->status);
    }

    public function test_falha_ao_mudar_status_de_ocorrencia_inexistente(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Occurrence não encontrada');

        app(OccurrenceService::class)->changeStatusById(
            (string) Str::uuid(),
            EnumOccurrenceStatus::IN_PROGRESS
        );
    }

    public function test_falha_ao_cancelar_ocorrencia_com_dispatch_existente(): void
    {
        $occurrence = $this->criarOcorrencia(EnumOccurrenceStatus::REPORTED);

        Dispatch::create([
            'occurrence_id' => $occurrence->id,
            'resource_code' => 'ABT-21',
            'status' => EnumDispatchStatus::ASSIGNED,
        ]);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Não é possível cancelar ocorrência com dispatch já criado.');

        app(OccurrenceService::class)->changeStatusById(
            $occurrence->id,
            EnumOccurrenceStatus::CANCELLED
        );
    }

    private function criarOcorrencia(EnumOccurrenceStatus $status): Occurrence
    {
        return Occurrence::create([
            'external_id' => (string) Str::uuid(),
            'type' => 'incendio_urbano',
            'status' => $status,
            'description' => 'Ocorrência para teste do OccurrenceService',
            'reported_at' => now(),
        ]);
    }
}
