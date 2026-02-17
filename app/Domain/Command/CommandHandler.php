<?php

namespace App\Domain\Command;

use App\Enums\EnumCommandTypes;
use App\Enums\EnumDispatchStatus;
use App\Enums\EnumOccurrenceStatus;
use App\Models\Command;
use App\Repositories\CommandRepository;
use App\Repositories\DispatchRepository;
use App\Services\DispatchService;
use App\Services\OccurrenceService;
use Illuminate\Support\Facades\DB;

class CommandHandler
{
    public function __construct(
        private OccurrenceService $occurrenceService,
        private DispatchService $dispatchService,
        private DispatchRepository $dispatchRepository,
        private CommandRepository $commandRepository
    ) {}

    public function handle(Command $command): void
    {
        app()->instance('audit.command_id', $command->id);

        try {
            DB::transaction(function () use ($command) {
                $type = $command->type instanceof EnumCommandTypes
                    ? $command->type
                    : EnumCommandTypes::from($command->type);

                match ($type) {
                    EnumCommandTypes::OCCURRENCE_CREATED =>
                    $this->occurrenceService->create($command->payload),

                    EnumCommandTypes::OCCURRENCE_IN_PROGRESS =>
                    $this->startOccurrence($command->payload['occurrenceId']),

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
                    $this->dispatchService->changeStatusByIdAndOccurrence(
                        $command->payload['dispatchId'],
                        $command->payload['occurrenceId'],
                        EnumDispatchStatus::ON_SITE
                    ),
                };

                $this->commandRepository->markAsProcessed($command);
            });
        } catch (\Throwable $e) {
            $errorMessage = $e instanceof \DomainException
                ? 'Erro por quebra de fluxo: ' . $e->getMessage()
                : $e->getMessage();

            $this->commandRepository
                ->markAsFailed($command, $errorMessage);

            throw $e;
        } finally {
            app()->forgetInstance('audit.command_id');
        }
    }

    private function startOccurrence(string $occurrenceId): void
    {
        if (!$this->dispatchRepository->existsByOccurrenceId($occurrenceId)) {
            throw new \DomainException('Só é possível iniciar ocorrência após ao menos um veículo no local.');
        }

        $this->occurrenceService->changeStatusById(
            $occurrenceId,
            EnumOccurrenceStatus::IN_PROGRESS
        );
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
