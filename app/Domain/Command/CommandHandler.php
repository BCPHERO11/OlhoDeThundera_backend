<?php

namespace App\Domain\Command;

use App\Enums\EnumCommandTypes;
use App\Enums\EnumDispatchStatus;
use App\Enums\EnumOccurrenceStatus;
use App\Models\Command;
use App\Repositories\CommandRepository;
use App\Services\DispatchService;
use App\Services\OccurrenceService;
use Illuminate\Support\Facades\DB;

class CommandHandler
{
    public function __construct(
        private OccurrenceService $occurrenceService,
        private DispatchService $dispatchService,
        private CommandRepository $commandRepository
    ) {}

    public function handle(Command $command): void
    {
        DB::transaction(function () use ($command) {
            try {
                $type = $command->type instanceof EnumCommandTypes
                    ? $command->type
                    : EnumCommandTypes::from($command->type);

                match ($type) {
                    EnumCommandTypes::OCCURRENCE_CREATED =>
                    $this->occurrenceService->create($command->payload),

                    EnumCommandTypes::OCCURRENCE_IN_PROGRESS =>
                    $this->occurrenceService->changeStatusById(
                        $command->payload['occurrenceId'],
                        EnumOccurrenceStatus::IN_PROGRESS
                    ),

                    EnumCommandTypes::OCCURRENCE_RESOLVED =>
                    $this->resolveOccurrenceAndCloseDispatches(
                        $command->payload['occurrenceId']
                    ),

                    EnumCommandTypes::OCCURRENCE_CANCELLED =>
                    $this->occurrenceService->changeStatusById(
                        $command->payload['occurrenceId'],
                        EnumOccurrenceStatus::CANCELLED
                    ),

                    EnumCommandTypes::DISPATCH_ASSIGNED =>
                    $this->dispatchService->create($command->payload),

                    EnumCommandTypes::DISPATCH_ON_SITE =>
                    $this->dispatchService->changeStatusByOccurrenceAndResource(
                        $command->payload['occurrenceId'],
                        $command->payload['resourceCode'],
                        EnumDispatchStatus::ON_SITE
                    ),
                };

                $this->commandRepository->markAsProcessed($command);
            } catch (\Throwable $e) {
                $this->commandRepository
                    ->markAsFailed($command, $e->getMessage());

                throw $e;
            }
        });
    }

    private function resolveOccurrenceAndCloseDispatches(string $occurrenceId): void
    {
        $this->occurrenceService->changeStatusById(
            $occurrenceId,
            EnumOccurrenceStatus::RESOLVED
        );

        $this->dispatchService->closeAllByOccurrenceId($occurrenceId);
    }
}
