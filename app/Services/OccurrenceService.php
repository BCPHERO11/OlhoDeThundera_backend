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
        $payload['status'] = EnumOccurrenceStatus::REPORTED;

        return $this->repository->create($payload);
    }

    public function changeStatus(
        string $externalId,
        EnumOccurrenceStatus $newStatus
    ) {
        return DB::transaction(function () use ($externalId, $newStatus) {

            $occurrence = $this->repository
                ->findByExternalIdForUpdate($externalId);

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
