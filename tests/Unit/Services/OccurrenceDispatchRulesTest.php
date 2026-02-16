<?php

namespace Tests\Unit\Services;

use App\Domain\Command\CommandHandler;
use App\Enums\EnumCommandStatus;
use App\Enums\EnumCommandTypes;
use App\Enums\EnumDispatchStatus;
use App\Enums\EnumOccurrenceStatus;
use App\Models\Command;
use App\Models\Dispatch;
use App\Models\Occurrence;
use App\Services\OccurrenceService;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OccurrenceDispatchRulesTest extends TestCase
{
    use RefreshDatabase;

    public function test_occurrence_cannot_be_cancelled_after_dispatch_exists(): void
    {
        $occurrence = Occurrence::create([
            'external_id' => '0b5fd65b-5f76-4d59-a3a9-9464186fcd11',
            'type' => 'incendio_urbano',
            'status' => EnumOccurrenceStatus::REPORTED,
            'description' => 'Ocorrência em aberto',
            'reported_at' => now(),
        ]);

        Dispatch::create([
            'occurrence_id' => $occurrence->id,
            'resource_code' => 'ABT-01',
            'status' => EnumDispatchStatus::ASSIGNED,
        ]);

        $this->expectException(DomainException::class);

        app(OccurrenceService::class)->changeStatusById(
            $occurrence->id,
            EnumOccurrenceStatus::CANCELLED
        );
    }

    public function test_occurrence_can_only_start_with_on_site_dispatch(): void
    {
        $occurrence = Occurrence::create([
            'external_id' => 'f22d9288-4f66-44af-8110-7adf92d963de',
            'type' => 'incendio_urbano',
            'status' => EnumOccurrenceStatus::REPORTED,
            'description' => 'Ocorrência em aberto',
            'reported_at' => now(),
        ]);

        Dispatch::create([
            'occurrence_id' => $occurrence->id,
            'resource_code' => 'ABT-01',
            'status' => EnumDispatchStatus::ASSIGNED,
        ]);

        $this->expectException(DomainException::class);

        app(OccurrenceService::class)->changeStatusById(
            $occurrence->id,
            EnumOccurrenceStatus::IN_PROGRESS
        );
    }

    public function test_resolving_occurrence_closes_all_dispatches(): void
    {
        $occurrence = Occurrence::create([
            'external_id' => '1eb4ddf0-52ec-48e8-a7dc-7ac1e665d53a',
            'type' => 'incendio_urbano',
            'status' => EnumOccurrenceStatus::IN_PROGRESS,
            'description' => 'Ocorrência em andamento',
            'reported_at' => now(),
        ]);

        Dispatch::create([
            'occurrence_id' => $occurrence->id,
            'resource_code' => 'ABT-01',
            'status' => EnumDispatchStatus::ON_SITE,
        ]);

        Dispatch::create([
            'occurrence_id' => $occurrence->id,
            'resource_code' => 'RES-02',
            'status' => EnumDispatchStatus::ASSIGNED,
        ]);

        $command = Command::create([
            'idempotency_key' => 'occurrence-resolved-command',
            'source' => 'test',
            'type' => EnumCommandTypes::OCCURRENCE_RESOLVED,
            'payload' => ['occurrenceId' => $occurrence->id],
            'status' => EnumCommandStatus::PENDING,
        ]);

        app(CommandHandler::class)->handle($command);

        $this->assertDatabaseHas('occurrences', [
            'id' => $occurrence->id,
            'status' => EnumOccurrenceStatus::RESOLVED->value,
        ]);

        $this->assertSame(
            0,
            Dispatch::where('occurrence_id', $occurrence->id)
                ->where('status', '!=', EnumDispatchStatus::CLOSED->value)
                ->count()
        );
    }
}
