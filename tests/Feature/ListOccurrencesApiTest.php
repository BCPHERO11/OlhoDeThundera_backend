<?php

namespace Tests\Feature;

use App\Enums\EnumOccurrenceStatus;
use App\Models\Occurrence;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ListOccurrencesApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_exige_autenticacao_por_api_key_para_listagem(): void
    {
        $response = $this->getJson('/api/occurrences');

        $response
            ->assertUnauthorized()
            ->assertJsonPath('error', 'Nao Autorizado');
    }

    public function test_lista_ocorrencias_com_filtro_por_status_e_tipo(): void
    {
        config()->set('services.integration.api_key', 'test-api-key');

        Occurrence::create([
            'external_id' => '0dcf4dd9-b9f0-4a78-8857-a0fac3d51b57',
            'type' => 'incendio',
            'status' => EnumOccurrenceStatus::REPORTED,
            'description' => 'Fogo em residência',
            'reported_at' => now()->subMinute(),
        ]);

        Occurrence::create([
            'external_id' => 'fd87dad3-d75e-4284-bc0f-5524f9954f84',
            'type' => 'resgate',
            'status' => EnumOccurrenceStatus::IN_PROGRESS,
            'description' => 'Resgate em altura',
            'reported_at' => now(),
        ]);

        $response = $this
            ->withHeader('X-API-Key', 'test-api-key')
            ->getJson('/api/occurrences?status=reported&type=incendio');

        $response
            ->assertOk()
            ->assertJsonPath('filters.status', 'reported')
            ->assertJsonPath('filters.type', 'incendio')
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.type', 'incendio')
            ->assertJsonPath('data.0.status', 'reported');
    }
}
