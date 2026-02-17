<?php
namespace App\Repositories;

use App\Enums\EnumOccurrenceStatus;
use App\Models\Occurrence;
use Illuminate\Database\Eloquent\Collection;

class OccurrenceRepository
{
    public function create(array $data): Occurrence
    {
        return Occurrence::create($data);
    }

    public function findByExternalIdForUpdate(string $externalId): ?Occurrence
    {
        return Occurrence::where('external_id', $externalId)
            ->lockForUpdate()
            ->first();
    }

    public function findByIdForUpdate(string $id): ?Occurrence
    {
        return Occurrence::where('id', $id)
            ->lockForUpdate()
            ->first();
    }

    public function save(Occurrence $occurrence): Occurrence
    {
        $occurrence->save();
        return $occurrence->refresh();
    }

    public function listByFilters(?string $status, ?string $type): Collection
    {
        return Occurrence::with('dispatches')
            ->where('status', $status ?? '*')
            ->where('type', $type ?? '*')
            ->orderByDesc('reported_at')
            ->get();
    }
}
