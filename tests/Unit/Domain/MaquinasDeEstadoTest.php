<?php

namespace Tests\Unit\Domain;

use App\Domain\StateMachines\DispatchStateMachine;
use App\Domain\StateMachines\OccurrenceStateMachine;
use App\Enums\EnumDispatchStatus;
use App\Enums\EnumOccurrenceStatus;
use DomainException;
use Tests\TestCase;

class MaquinasDeEstadoTest extends TestCase
{
    public function test_maquina_de_estados_de_ocorrencia_permite_transicao_valida(): void
    {
        $machine = new OccurrenceStateMachine();

        $machine->validate(
            EnumOccurrenceStatus::REPORTED,
            EnumOccurrenceStatus::IN_PROGRESS
        );

        $this->assertTrue(true);
    }

    public function test_maquina_de_estados_de_ocorrencia_bloqueia_transicao_invalida(): void
    {
        $machine = new OccurrenceStateMachine();

        $this->expectException(DomainException::class);

        $machine->validate(
            EnumOccurrenceStatus::REPORTED,
            EnumOccurrenceStatus::RESOLVED
        );
    }

    public function test_maquina_de_estados_de_dispatch_permite_transicao_valida(): void
    {
        $machine = new DispatchStateMachine();

        $machine->validate(
            EnumDispatchStatus::ASSIGNED,
            EnumDispatchStatus::EN_ROUTE
        );

        $this->assertTrue(true);
    }

    public function test_maquina_de_estados_de_dispatch_bloqueia_transicao_direta_invalida_de_assigned_para_on_site(): void
    {
        $machine = new DispatchStateMachine();

        $this->expectException(DomainException::class);

        $machine->validate(
            EnumDispatchStatus::ASSIGNED,
            EnumDispatchStatus::ON_SITE
        );
    }
}
