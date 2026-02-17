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

class AtributosImutaveisTest extends TestCase
{
    use RefreshDatabase;

    public function test_ocorrencia_gera_id_e_bloqueia_alteracao_de_id_e_external_id(): void
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

    public function test_dispatch_gera_id_e_bloqueia_alteracao_de_id(): void
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

    public function test_comando_gera_id_e_bloqueia_alteracoes_imutaveis(): void
    {
        $command = Command::create([
            'idempotency_key' => 'chave-' . Str::uuid(),
            'source' => 'teste',
            'type' => EnumCommandTypes::OCCURRENCE_CREATED,
            'payload' => ['externalId' => (string) Str::uuid()],
            'status' => EnumCommandStatus::PENDING,
        ]);

        $this->assertNotNull($command->id);

        $command->idempotency_key = 'nova-chave';
        $this->expectException(LogicException::class);
        $command->save();
    }

    public function test_ocorrencia_criada_gera_audit_log_com_meta_do_comando(): void
    {
        app()->instance('audit.command_id', 'cmd-occ-created');

        $occurrence = Occurrence::create([
            'external_id' => (string) Str::uuid(),
            'type' => 'incendio_urbano',
            'status' => 0,
            'description' => 'Teste',
            'reported_at' => now(),
        ]);

        $log = AuditLog::where('entity_type', 'occurrence')
            ->where('entity_id', $occurrence->id)
            ->where('action', 'created')
            ->first();

        $this->assertNotNull($log);
        $this->assertSame($occurrence->id, $log->entity_id);
        $this->assertSame('incendio_urbano', $log->after['type']);
        $this->assertSame('cmd-occ-created', $log->meta['command_id']);

        app()->forgetInstance('audit.command_id');
    }

    public function test_dispatch_criado_gera_audit_log_com_meta_do_comando(): void
    {
        app()->instance('audit.command_id', 'cmd-disp-created');

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

        $log = AuditLog::where('entity_type', 'dispatch')
            ->where('entity_id', $dispatch->id)
            ->where('action', 'created')
            ->first();

        $this->assertNotNull($log);
        $this->assertSame($dispatch->id, $log->entity_id);
        $this->assertSame('AMB-01', $log->after['resource_code']);
        $this->assertSame('cmd-disp-created', $log->meta['command_id']);

        app()->forgetInstance('audit.command_id');
    }

    public function test_ocorrencia_modificada_gera_audit_log_com_before_after_e_meta_do_comando(): void
    {
        $occurrence = Occurrence::create([
            'external_id' => (string) Str::uuid(),
            'type' => 'incendio_urbano',
            'status' => 0,
            'description' => 'Descrição antiga',
            'reported_at' => now(),
        ]);

        app()->instance('audit.command_id', 'cmd-occ-updated');

        $occurrence->description = 'Descrição nova';
        $occurrence->save();

        $log = AuditLog::where('entity_type', 'occurrence')
            ->where('entity_id', $occurrence->id)
            ->where('action', 'updated')
            ->latest('id')
            ->first();

        $this->assertNotNull($log);
        $this->assertSame('Descrição antiga', $log->before['description']);
        $this->assertSame('Descrição nova', $log->after['description']);
        $this->assertSame('cmd-occ-updated', $log->meta['command_id']);

        app()->forgetInstance('audit.command_id');
    }


    public function test_dispatch_modificado_gera_audit_log_com_before_after_e_meta_do_comando(): void
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

        app()->instance('audit.command_id', 'cmd-disp-updated');

        $dispatch->resource_code = 'AMB-02';
        $dispatch->save();

        $log = AuditLog::where('entity_type', 'dispatch')
            ->where('entity_id', $dispatch->id)
            ->where('action', 'updated')
            ->latest('id')
            ->first();

        $this->assertNotNull($log);
        $this->assertSame('AMB-01', $log->before['resource_code']);
        $this->assertSame('AMB-02', $log->after['resource_code']);
        $this->assertSame('cmd-disp-updated', $log->meta['command_id']);

        app()->forgetInstance('audit.command_id');
    }

    public function test_audit_log_bloqueia_alteracao_de_vinculo_da_entidade(): void
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
            'meta' => ['source' => 'teste'],
            'created_at' => now(),
        ]);

        $log->entity_id = (string) Str::uuid();
        $this->expectException(LogicException::class);
        $log->save();
    }
}
