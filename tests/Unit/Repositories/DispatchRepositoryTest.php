<?php

namespace Tests\Unit\Repositories;

use App\Enums\EnumDispatchStatus;
use App\Enums\EnumOccurrenceStatus;
use App\Models\Dispatch;
use App\Models\Occurrence;
use App\Repositories\DispatchRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class DispatchRepositoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_e_find_por_id_e_ocorrencia_funcionam(): void
    {
        $repository = app(DispatchRepository::class);
        $occurrence = $this->criarOcorrencia();

        $dispatch = $repository->create([
            'occurrence_id' => $occurrence->id,
            'resource_code' => 'ABT-31',
            'status' => EnumDispatchStatus::ASSIGNED,
        ]);

        $this->assertNotNull($repository->findByIdForUpdate($dispatch->id));
        $this->assertNotNull($repository->findByIdAndOccurrenceForUpdate($dispatch->id, $occurrence->id));
    }

    public function test_exists_por_ocorrencia_e_por_status_funcionam(): void
    {
        $repository = app(DispatchRepository::class);
        $occurrence = $this->criarOcorrencia();

        $repository->create([
            'occurrence_id' => $occurrence->id,
            'resource_code' => 'ABT-32',
            'status' => EnumDispatchStatus::ON_SITE,
        ]);

        $this->assertTrue($repository->existsByOccurrenceId($occurrence->id));
        $this->assertTrue($repository->existsByOccurrenceIdAndStatus($occurrence->id, EnumDispatchStatus::ON_SITE));
    }

    public function test_close_all_e_save_atualizam_registros(): void
    {
        $repository = app(DispatchRepository::class);
        $occurrence = $this->criarOcorrencia();

        $dispatch = Dispatch::create([
            'occurrence_id' => $occurrence->id,
            'resource_code' => 'ABT-33',
            'status' => EnumDispatchStatus::ASSIGNED,
        ]);

        $repository->closeAllByOccurrenceId($occurrence->id);
        $this->assertSame(EnumDispatchStatus::CLOSED, $dispatch->fresh()->status);

        $dispatch->status = EnumDispatchStatus::EN_ROUTE;
        $salvo = $repository->save($dispatch);
        $this->assertSame(EnumDispatchStatus::EN_ROUTE, $salvo->status);
    }

    private function criarOcorrencia(): Occurrence
    {
        return Occurrence::create([
            'external_id' => (string) Str::uuid(),
            'type' => 'incendio_urbano',
            'status' => EnumOccurrenceStatus::REPORTED,
            'description' => 'Ocorrência base para DispatchRepository',
            'reported_at' => now(),
        ]);
    }
}
