<?php

namespace Tests\Unit\Services;

use App\Enums\EnumDispatchStatus;
use App\Enums\EnumOccurrenceStatus;
use App\Models\Dispatch;
use App\Models\Occurrence;
use App\Services\DispatchService;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class DispatchServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_cria_dispatch_quando_ocorrencia_esta_valida(): void
    {
        $occurrence = $this->criarOcorrencia(EnumOccurrenceStatus::REPORTED);

        $dispatch = app(DispatchService::class)->create([
            'occurrenceId' => $occurrence->id,
            'resourceCode' => 'ABT-07',
        ]);

        $this->assertSame($occurrence->id, $dispatch->occurrence_id);
        $this->assertSame(EnumDispatchStatus::ASSIGNED, $dispatch->status);
    }

    public function test_nao_cria_dispatch_para_ocorrencia_cancelada(): void
    {
        $occurrence = $this->criarOcorrencia(EnumOccurrenceStatus::CANCELLED);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Ocorrência já cancelada');

        app(DispatchService::class)->create([
            'occurrenceId' => $occurrence->id,
            'resourceCode' => 'ABT-08',
        ]);
    }

    public function test_altera_status_do_dispatch_por_id_e_ocorrencia(): void
    {
        $occurrence = $this->criarOcorrencia(EnumOccurrenceStatus::IN_PROGRESS);

        $dispatch = Dispatch::create([
            'occurrence_id' => $occurrence->id,
            'resource_code' => 'ABT-09',
            'status' => EnumDispatchStatus::EN_ROUTE,
        ]);

        $atualizado = app(DispatchService::class)->changeStatusByIdAndOccurrence(
            $dispatch->id,
            $occurrence->id,
            EnumDispatchStatus::ON_SITE
        );

        $this->assertSame(EnumDispatchStatus::ON_SITE, $atualizado->status);
    }

    public function test_falha_ao_alterar_status_quando_dispatch_nao_existe_na_ocorrencia(): void
    {
        $occurrence = $this->criarOcorrencia(EnumOccurrenceStatus::IN_PROGRESS);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Dispatch não encontrado para ocorrência informada');

        app(DispatchService::class)->changeStatusByIdAndOccurrence(
            (string) Str::uuid(),
            $occurrence->id,
            EnumDispatchStatus::ON_SITE
        );
    }

    public function test_fecha_todos_os_dispatches_da_ocorrencia(): void
    {
        $occurrence = $this->criarOcorrencia(EnumOccurrenceStatus::IN_PROGRESS);

        Dispatch::create([
            'occurrence_id' => $occurrence->id,
            'resource_code' => 'ABT-10',
            'status' => EnumDispatchStatus::ASSIGNED,
        ]);

        Dispatch::create([
            'occurrence_id' => $occurrence->id,
            'resource_code' => 'ABT-11',
            'status' => EnumDispatchStatus::ON_SITE,
        ]);

        app(DispatchService::class)->closeAllByOccurrenceId($occurrence->id);

        $abertos = Dispatch::where('occurrence_id', $occurrence->id)
            ->where('status', '!=', EnumDispatchStatus::CLOSED->value)
            ->count();

        $this->assertSame(0, $abertos);
    }

    private function criarOcorrencia(EnumOccurrenceStatus $status): Occurrence
    {
        return Occurrence::create([
            'external_id' => (string) Str::uuid(),
            'type' => 'incendio_urbano',
            'status' => $status,
            'description' => 'Ocorrência para teste do DispatchService',
            'reported_at' => now(),
        ]);
    }
}
