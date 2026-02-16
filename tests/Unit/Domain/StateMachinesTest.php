<?php

namespace Tests\Unit\Domain;

use App\Domain\StateMachines\DispatchStateMachine;
use App\Domain\StateMachines\OccurrenceStateMachine;
use App\Enums\EnumDispatchStatus;
use App\Enums\EnumOccurrenceStatus;
use DomainException;
use Tests\TestCase;

class StateMachinesTest extends TestCase
{
    public function test_occurrence_state_machine_allows_valid_transition(): void
    {
        $machine = new OccurrenceStateMachine();

        $machine->validate(
            EnumOccurrenceStatus::REPORTED,
            EnumOccurrenceStatus::IN_PROGRESS
        );

        $this->assertTrue(true);
    }

    public function test_occurrence_state_machine_blocks_invalid_transition(): void
    {
        $machine = new OccurrenceStateMachine();

        $this->expectException(DomainException::class);

        $machine->validate(
            EnumOccurrenceStatus::REPORTED,
            EnumOccurrenceStatus::RESOLVED
        );
    }

    public function test_dispatch_state_machine_allows_valid_transition(): void
    {
        $machine = new DispatchStateMachine();

        $machine->validate(
            EnumDispatchStatus::ASSIGNED,
            EnumDispatchStatus::EN_ROUTE
        );

        $this->assertTrue(true);
    }

    public function test_dispatch_state_machine_blocks_invalid_direct_transition_from_assigned_to_on_site(): void
    {
        $machine = new DispatchStateMachine();

        $this->expectException(DomainException::class);

        $machine->validate(
            EnumDispatchStatus::ASSIGNED,
            EnumDispatchStatus::ON_SITE
        );
    }
}
