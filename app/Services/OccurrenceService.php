<?php
namespace App\Services;

use App\Domain\StateMachines\OccurrenceStateMachine;
use App\Enums\EnumDispatchStatus;
use App\Enums\EnumOccurrenceStatus;
use App\Repositories\DispatchRepository;
use App\Repositories\OccurrenceRepository;
use Illuminate\Support\Facades\DB;

class OccurrenceService
{
    public function __construct(
        private OccurrenceRepository $repository,
        private DispatchRepository $dispatchRepository,
        private OccurrenceStateMachine $stateMachine
    ) {}

    public function create(array $payload)
    {
        $occurrenceData = [
            'external_id' => $payload['externalId'],
            'type' => $payload['type'],
            'description' => $payload['description'],
            'reported_at' => $payload['reportedAt'],
            'status' => EnumOccurrenceStatus::REPORTED,
        ];

        return $this->repository->create($occurrenceData);
    }

    public function changeStatusById(
        string $occurrenceId,
        EnumOccurrenceStatus $newStatus
    ) {
        return DB::transaction(function () use ($occurrenceId, $newStatus) {
            $occurrence = $this->repository
                ->findByIdForUpdate($occurrenceId);

            if (!$occurrence) {
                throw new \DomainException('Occurrence não encontrada');
            }

            $this->assertBusinessRules($occurrenceId, $newStatus);

            $this->stateMachine->validate(
                $occurrence->status,
                $newStatus
            );

            $occurrence->status = $newStatus;

            return $this->repository->save($occurrence);
        });
    }

    private function assertBusinessRules(
        string $occurrenceId,
        EnumOccurrenceStatus $newStatus
    ): void {
        if (
            $newStatus === EnumOccurrenceStatus::CANCELLED
            && $this->dispatchRepository->existsByOccurrenceId($occurrenceId)
        ) {
            throw new \DomainException('Não é possível cancelar ocorrência com dispatch já criado.');
        }

        if (
            $newStatus === EnumOccurrenceStatus::IN_PROGRESS
            && !$this->dispatchRepository->existsByOccurrenceIdAndStatus(
                $occurrenceId,
                EnumDispatchStatus::ON_SITE
            )
        ) {
            throw new \DomainException('Só é possível iniciar ocorrência após ao menos um dispatch ON_SITE.');
        }
    }
}
