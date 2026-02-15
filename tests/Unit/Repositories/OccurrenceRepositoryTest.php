<?php

namespace Tests\Unit\Repositories;

use App\Repositories\OccurrenceRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class OccurrenceRepositoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_persists_generated_id_when_not_provided(): void
    {
        $repository = app(OccurrenceRepository::class);

        $occurrence = $repository->create([
            'external_id' => (string) Str::uuid(),
            'type' => 'incendio_urbano',
            'status' => 0,
            'description' => 'Ocorrência de teste',
            'reported_at' => now(),
        ]);

        $this->assertNotNull($occurrence->id);
        $this->assertDatabaseHas('occurrences', [
            'id' => $occurrence->id,
            'external_id' => $occurrence->external_id,
        ]);
    }
}
