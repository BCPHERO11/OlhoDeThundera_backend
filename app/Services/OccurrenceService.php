<?php
namespace App\Services;

use App\Repositories\OccurrenceRepository;
use App\Domain\StateMachines\OccurrenceStateMachine;
use App\Enums\EnumOccurrenceStatus;
use Illuminate\Support\Facades\DB;

class OccurrenceService
{
    public function __construct(
        private OccurrenceRepository $repository,
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
                throw new \DomainException("Occurrence não encontrada");
            }

            $this->stateMachine->validate(
                $occurrence->status,
                $newStatus
            );

            $occurrence->status = $newStatus;

            return $this->repository->save($occurrence);
        });
    }
}
