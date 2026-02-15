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
        $idempotencyKey = $this->payload['idempotency_key'] ?? null;

        if (!$idempotencyKey) {
            throw new \InvalidArgumentException('idempotency_key ausente no payload');
        }

        $command = null;

        try {
            DB::transaction(function () use (
                $idempotencyKey,
                $handler,
                $commandRepository,
                &$command
            ) {
                $command = Command::where('idempotency_key', $idempotencyKey)
                    ->lockForUpdate()
                    ->first();

                if (!$command) {
                    $command = $commandRepository->create($this->payload);
                }

                if ($command->status === EnumCommandStatus::PROCESSED) {
                    return;
                }

                $handler->handle($command);
            });
        } catch (Throwable $e) {
            if ($command) {
                $commandRepository->markAsFailed(
                    $command,
                    $e->getMessage()
                );
            }

            throw $e;
        }
    }

    /**
     * Caso o job exceda todas as tentativas
     */
    public function failed(Throwable $exception): void
    {
        $idempotencyKey = $this->payload['idempotency_key'] ?? null;

        if (!$idempotencyKey) {
            return;
        }

        $command = Command::where('idempotency_key', $idempotencyKey)->first();

        if ($command) {
            app(CommandRepository::class)
                ->markAsFailed(
                    $command,
                    $exception->getMessage()
                );
        }
    }
}
