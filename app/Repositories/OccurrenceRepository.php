<?php
namespace App\Repositories;

use App\Models\Occurrence;
use Illuminate\Support\Str;

class OccurrenceRepository
{
    public function create(array $data): Occurrence
    {
        $data['id'] ??= (string) Str::uuid();

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
}
