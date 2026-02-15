<?php
namespace App\Repositories;

use App\Models\Dispatch;
use Illuminate\Support\Str;

class DispatchRepository
{
    public function create(array $data): Dispatch
    {
        $data['id'] ??= (string) Str::uuid();

        return Dispatch::create($data);
    }

    public function findByIdForUpdate(string $id): ?Dispatch
    {
        return Dispatch::where('id', $id)
            ->lockForUpdate()
            ->first();
    }

    public function save(Dispatch $dispatch): Dispatch
    {
        $dispatch->save();
        return $dispatch->refresh();
    }
}
