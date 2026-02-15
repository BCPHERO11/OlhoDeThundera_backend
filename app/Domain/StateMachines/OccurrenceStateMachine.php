<?php
namespace App\Domain\StateMachines;

use App\Enums\EnumOccurrenceStatus;
use DomainException;

class OccurrenceStateMachine
{
    private array $transitions = [
        EnumOccurrenceStatus::REPORTED->value => [
            EnumOccurrenceStatus::IN_PROGRESS,
            EnumOccurrenceStatus::CANCELLED,
        ],
        EnumOccurrenceStatus::IN_PROGRESS->value => [
            EnumOccurrenceStatus::RESOLVED,
            EnumOccurrenceStatus::CANCELLED,
        ],
        EnumOccurrenceStatus::RESOLVED->value => [],
        EnumOccurrenceStatus::CANCELLED->value => [],
    ];

    public function validate(
        EnumOccurrenceStatus $current,
        EnumOccurrenceStatus $next
    ): void {
        $allowed = $this->transitions[$current->value] ?? [];

        if (!in_array($next, $allowed, true)) {
            throw new DomainException(
                "Transição inválida: {$current->name()} → {$next->name()}"
            );
        }
    }
}
