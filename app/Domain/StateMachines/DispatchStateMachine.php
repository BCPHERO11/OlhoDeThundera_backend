<?php
namespace App\Domain\StateMachines;

use App\Enums\EnumDispatchStatus;
use DomainException;

class DispatchStateMachine
{
    private array $transitions = [
        EnumDispatchStatus::ASSIGNED->value => [
            EnumDispatchStatus::EN_ROUTE,
        ],
        EnumDispatchStatus::EN_ROUTE->value => [
            EnumDispatchStatus::ON_SITE,
        ],
        EnumDispatchStatus::ON_SITE->value => [
            EnumDispatchStatus::CLOSED,
        ],
        EnumDispatchStatus::CLOSED->value => [],
    ];

    public function validate(
        EnumDispatchStatus $current,
        EnumDispatchStatus $next
    ): void {
        $allowed = $this->transitions[$current->value] ?? [];

        if (!in_array($next, $allowed, true)) {
            throw new DomainException(
                "Transição inválida Dispatch: {$current->name()} → {$next->name()}"
            );
        }
    }
}
