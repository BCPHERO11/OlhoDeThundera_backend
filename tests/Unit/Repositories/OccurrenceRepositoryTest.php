<?php

namespace Tests\Unit\Repositories;

use App\Enums\EnumOccurrenceStatus;
use App\Models\Occurrence;
use App\Repositories\OccurrenceRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class OccurrenceRepositoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_criacao_persiste_ocorrencia_com_id_gerado(): void
    {
        $repository = app(OccurrenceRepository::class);

        $occurrence = $repository->create([
            'external_id' => (string) Str::uuid(),
            'type' => 'incendio_urbano',
            'status' => EnumOccurrenceStatus::REPORTED,
            'description' => 'Ocorrência de teste',
            'reported_at' => now(),
        ]);

        $this->assertNotNull($occurrence->id);
        $this->assertDatabaseHas('occurrences', ['id' => $occurrence->id]);
    }

    public function test_busca_por_external_id_e_por_id_funciona(): void
    {
        $repository = app(OccurrenceRepository::class);
        $occurrence = $this->criarOcorrencia('incendio_urbano', EnumOccurrenceStatus::REPORTED);

        $this->assertSame(
            $occurrence->id,
            $repository->findByExternalIdForUpdate($occurrence->external_id)?->id
        );

        $this->assertSame(
            $occurrence->id,
            $repository->findByIdForUpdate($occurrence->id)?->id
        );
    }

    public function test_salvar_atualiza_status_da_ocorrencia(): void
    {
        $repository = app(OccurrenceRepository::class);
        $occurrence = $this->criarOcorrencia('incendio_urbano', EnumOccurrenceStatus::REPORTED);

        $occurrence->status = EnumOccurrenceStatus::IN_PROGRESS;
        $salva = $repository->save($occurrence);

        $this->assertSame(EnumOccurrenceStatus::IN_PROGRESS, $salva->status);
    }

    public function test_listagem_por_filtros_aplica_status_e_tipo(): void
    {
        $repository = app(OccurrenceRepository::class);

        $this->criarOcorrencia('incendio_urbano', EnumOccurrenceStatus::IN_PROGRESS);
        $this->criarOcorrencia('deslizamento', EnumOccurrenceStatus::IN_PROGRESS);
        $this->criarOcorrencia('incendio_urbano', EnumOccurrenceStatus::REPORTED);

        $resultado = $repository->listByFilters('in_progress', 'incendio_urbano');

        $this->assertCount(1, $resultado);
        $this->assertSame('incendio_urbano', $resultado->first()->type);
        $this->assertSame(EnumOccurrenceStatus::IN_PROGRESS, $resultado->first()->status);
    }

    private function criarOcorrencia(string $type, EnumOccurrenceStatus $status): Occurrence
    {
        return Occurrence::create([
            'external_id' => (string) Str::uuid(),
            'type' => $type,
            'status' => $status,
            'description' => 'Ocorrência para teste do OccurrenceRepository',
            'reported_at' => now(),
        ]);
    }
}
