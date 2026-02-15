<?php
namespace App\Repositories;

use App\Models\Command;
use App\Enums\EnumCommandStatus;

// TODO corrigir, ainda falta  bastate coisa nesse negócio aqui
class CommandRepository
{
    public function create(array $data): Command
    {
        return Command::create($data);
    }

    public function markAsProcessed(Command $command): void
    {
        $command->update([
            'status' => EnumCommandStatus::PROCESSED,
            'processed_at' => now(),
        ]);
    }

    public function markAsFailed(Command $command, string $error): void
    {
        $command->update([
            'status' => EnumCommandStatus::FAILED,
            'error' => $error,
            'processed_at' => now(),
        ]);
    }
}
