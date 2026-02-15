<?php

namespace Tests\Unit\Models;

use App\Enums\EnumCommandStatus;
use App\Enums\EnumCommandTypes;
use App\Models\AuditLog;
use App\Models\Command;
use App\Models\Dispatch;
use App\Models\Occurrence;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use LogicException;
use Tests\TestCase;

class ImmutableAttributesTest extends TestCase
{
    use RefreshDatabase;

    public function test_occurrence_generates_id_and_blocks_id_and_external_id_changes(): void
    {
        $occurrence = Occurrence::create([
            'external_id' => (string) Str::uuid(),
            'type' => 'incendio_urbano',
            'status' => 0,
            'description' => 'Teste',
            'reported_at' => now(),
        ]);

        $this->assertNotNull($occurrence->id);

        $occurrence->external_id = (string) Str::uuid();
        $this->expectException(LogicException::class);
        $occurrence->save();
    }

    public function test_dispatch_generates_id_and_blocks_id_changes(): void
    {
        $occurrence = Occurrence::create([
            'external_id' => (string) Str::uuid(),
            'type' => 'incendio_urbano',
            'status' => 0,
            'description' => 'Teste',
            'reported_at' => now(),
        ]);

        $dispatch = Dispatch::create([
            'occurrence_id' => $occurrence->id,
            'resource_code' => 'AMB-01',
            'status' => 0,
        ]);

        $this->assertNotNull($dispatch->id);

        $dispatch->id = (string) Str::uuid();
        $this->expectException(LogicException::class);
        $dispatch->save();
    }

    public function test_command_generates_id_and_blocks_immutable_changes(): void
    {
        $command = Command::create([
            'idempotency_key' => 'key-' . Str::uuid(),
            'source' => 'test',
            'type' => EnumCommandTypes::OCCURRENCE_CREATED,
            'payload' => ['externalId' => (string) Str::uuid()],
            'status' => EnumCommandStatus::PENDING,
        ]);

        $this->assertNotNull($command->id);

        $command->idempotency_key = 'new-key';
        $this->expectException(LogicException::class);
        $command->save();
    }

    public function test_audit_log_blocks_entity_binding_changes(): void
    {
        $occurrence = Occurrence::create([
            'external_id' => (string) Str::uuid(),
            'type' => 'incendio_urbano',
            'status' => 0,
            'description' => 'Teste',
            'reported_at' => now(),
        ]);

        $log = AuditLog::create([
            'entity_type' => 'occurrence',
            'entity_id' => $occurrence->id,
            'action' => 'created',
            'before' => null,
            'after' => ['status' => 0],
            'meta' => ['source' => 'test'],
            'created_at' => now(),
        ]);

        $log->entity_id = (string) Str::uuid();
        $this->expectException(LogicException::class);
        $log->save();
    }
}
