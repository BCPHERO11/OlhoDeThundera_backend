<?php
namespace App\Services;

use App\Domain\StateMachines\DispatchStateMachine;
use App\Enums\EnumDispatchStatus;
use App\Enums\EnumOccurrenceStatus;
use App\Repositories\DispatchRepository;
use App\Repositories\OccurrenceRepository;
use Illuminate\Support\Facades\DB;

class DispatchService
{
    public function __construct(
        private DispatchRepository $repository,
        private OccurrenceRepository $occurrenceRepository,
        private DispatchStateMachine $stateMachine
    ) {}

    public function create(array $payload)
    {
        return DB::transaction(function () use ($payload) {
            $occurrence = $this->occurrenceRepository
                ->findByIdForUpdate($payload['occurrenceId']);

            if (!$occurrence) {
                throw new \DomainException('Occurrence não encontrada para despacho');
            }

            if ($occurrence->status === EnumOccurrenceStatus::CANCELLED) {
                throw new \DomainException('Ocorrência já cancelada');
            }

            if ($occurrence->status === EnumOccurrenceStatus::RESOLVED) {
                throw new \DomainException('Não é possível criar dispatch para ocorrência finalizada.');
            }

            $dispatchData = [
                'occurrence_id' => $payload['occurrenceId'],
                'resource_code' => $payload['resourceCode'],
                'status' => EnumDispatchStatus::ASSIGNED,
            ];

            return $this->repository->create($dispatchData);
        });
    }

    public function changeStatusByIdAndOccurrence(
        string $dispatchId,
        string $occurrenceId,
        EnumDispatchStatus $newStatus
    ) {
        return DB::transaction(function () use ($dispatchId, $occurrenceId, $newStatus) {
            $dispatch = $this->repository
                ->findByIdAndOccurrenceForUpdate($dispatchId, $occurrenceId);

            if (!$dispatch) {
                throw new \DomainException('Dispatch não encontrado para ocorrência informada');
            }

            $this->stateMachine->validate(
                $dispatch->status,
                $newStatus
            );

            $dispatch->status = $newStatus;

            return $this->repository->save($dispatch);
        });
    }

    public function closeAllByOccurrenceId(string $occurrenceId): void
    {
        $this->repository->closeAllByOccurrenceId($occurrenceId);
    }
}
