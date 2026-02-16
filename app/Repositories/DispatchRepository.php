<?php
namespace App\Repositories;

use App\Enums\EnumDispatchStatus;
use App\Models\Dispatch;

class DispatchRepository
{
    public function create(array $data): Dispatch
    {
        return Dispatch::create($data);
    }

    public function findByIdForUpdate(string $id): ?Dispatch
    {
        return Dispatch::where('id', $id)
            ->lockForUpdate()
            ->first();
    }


    public function findByIdAndOccurrenceForUpdate(
        string $dispatchId,
        string $occurrenceId
    ): ?Dispatch {
        return Dispatch::where('id', $dispatchId)
            ->where('occurrence_id', $occurrenceId)
            ->lockForUpdate()
            ->first();
    }

    public function existsByOccurrenceId(string $occurrenceId): bool
    {
        return Dispatch::where('occurrence_id', $occurrenceId)->exists();
    }

    public function existsByOccurrenceIdAndStatus(
        string $occurrenceId,
        EnumDispatchStatus $status
    ): bool {
        return Dispatch::where('occurrence_id', $occurrenceId)
            ->where('status', $status)
            ->exists();
    }

    public function closeAllByOccurrenceId(string $occurrenceId): void
    {
        Dispatch::where('occurrence_id', $occurrenceId)
            ->where('status', '!=', EnumDispatchStatus::CLOSED->value)
            ->update(['status' => EnumDispatchStatus::CLOSED]);
    }

    public function save(Dispatch $dispatch): Dispatch
    {
        $dispatch->save();

        return $dispatch->refresh();
    }
}
