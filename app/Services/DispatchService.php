<?php
namespace App\Services;

use App\Repositories\DispatchRepository;
use App\Domain\StateMachines\DispatchStateMachine;
use App\Enums\EnumDispatchStatus;
use Illuminate\Support\Facades\DB;

class DispatchService
{
    public function __construct(
        private DispatchRepository $repository,
        private DispatchStateMachine $stateMachine
    ) {}

    public function create(array $payload)
    {
        $payload['status'] = EnumDispatchStatus::ASSIGNED;

        return $this->repository->create($payload);
    }

    public function changeStatus(
        string $id,
        EnumDispatchStatus $newStatus
    ) {
        return DB::transaction(function () use ($id, $newStatus) {

            $dispatch = $this->repository
                ->findByIdForUpdate($id);

            if (!$dispatch) {
                throw new \DomainException("Dispatch não encontrado");
            }

            $this->stateMachine->validate(
                $dispatch->status,
                $newStatus
            );

            $dispatch->status = $newStatus;

            return $this->repository->save($dispatch);
        });
    }
}
