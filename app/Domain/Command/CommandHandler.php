<?php

namespace App\Domain\Command;

use App\Models\Command;
use App\Enums\EnumCommandTypes;
use App\Enums\EnumOccurrenceStatus;
use App\Enums\EnumDispatchStatus;
use App\Repositories\CommandRepository;
use App\Services\OccurrenceService;
use App\Services\DispatchService;
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
                    $this->occurrenceService->create(
                        $command->payload
                    ),

                    EnumCommandTypes::OCCURRENCE_IN_PROGRESS =>
                    $this->occurrenceService->changeStatusById(
                        $command->payload['occurrenceId'],
                        EnumOccurrenceStatus::IN_PROGRESS
                    ),

                    EnumCommandTypes::OCCURRENCE_RESOLVED =>
                    $this->occurrenceService->changeStatusById(
                        $command->payload['occurrenceId'],
                        EnumOccurrenceStatus::RESOLVED
                    ),

                    EnumCommandTypes::OCCURRENCE_CANCELLED =>
                    $this->occurrenceService->changeStatusById(
                        $command->payload['occurrenceId'],
                        EnumOccurrenceStatus::CANCELLED
                    ),

                    EnumCommandTypes::DISPATCH_ASSIGNED =>
                    $this->dispatchService->create(
                        $command->payload
                    ),

                    EnumCommandTypes::DISPATCH_EN_ROUTE =>
                    $this->dispatchService->changeStatus(
                        $command->payload['dispatchId'],
                        EnumDispatchStatus::EN_ROUTE
                    ),

                    EnumCommandTypes::DISPATCH_ON_SITE =>
                    $this->dispatchService->changeStatus(
                        $command->payload['dispatchId'],
                        EnumDispatchStatus::ON_SITE
                    ),

                    EnumCommandTypes::DISPATCH_CLOSED =>
                    $this->dispatchService->changeStatus(
                        $command->payload['dispatchId'],
                        EnumDispatchStatus::CLOSED
                    ),
                };

                $this->commandRepository
                    ->markAsProcessed($command);

            } catch (\Throwable $e) {

                $this->commandRepository
                    ->markAsFailed($command, $e->getMessage());

                throw $e;
            }
        });
    }
}
