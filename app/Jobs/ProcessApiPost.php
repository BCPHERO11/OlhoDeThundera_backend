<?php
namespace App\Jobs;

use App\Models\Command;
use App\Repositories\CommandRepository;
use App\Domain\Command\CommandHandler;
use App\Enums\EnumCommandStatus;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Throwable;

class ProcessApiPost implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 10;

    public function __construct(
        public array $payload
    ) {}

    public function handle(
        CommandHandler $handler,
        CommandRepository $commandRepository
    ): void {

        $commandId = $this->payload['command_id'] ?? null;

        if (!$commandId) {
            throw new \InvalidArgumentException('command_id ausente no payload');
        }

        try {

            DB::transaction(function () use (
                $commandId,
                $handler,
                $commandRepository
            ) {

                $command = Command::where('id', $commandId)
                    ->lockForUpdate()
                    ->firstOrFail();

                // Se já processado, evita duplicidade
                if ($command->status === EnumCommandStatus::PROCESSED) {
                    return;
                }

                $handler->handle($command);
            });

        } catch (Throwable $e) {

            // Se falhou antes da transação completar
            $command = Command::find($commandId);

            if ($command) {
                $commandRepository->markAsFailed(
                    $command,
                    $e->getMessage()
                );
            }

            throw $e; // mantém retry funcionando
        }
    }

    /**
     * Caso o job exceda todas as tentativas
     */
    public function failed(Throwable $exception): void
    {
        $commandId = $this->payload['command_id'] ?? null;

        if (!$commandId) {
            return;
        }

        $command = Command::find($commandId);

        if ($command) {
            app(CommandRepository::class)
                ->markAsFailed(
                    $command,
                    $exception->getMessage()
                );
        }
    }
}
