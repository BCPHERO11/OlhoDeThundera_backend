<?php
namespace App\Jobs;

use App\Domain\Command\CommandHandler;
use App\Enums\EnumCommandStatus;
use App\Models\Command;
use App\Repositories\CommandRepository;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use DomainException;
use Throwable;

class ProcessApiPost implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 10;

    public function __construct(
        public array $payload,
        public string $idempotencyKey
    ) {}

    public function handle(
        CommandHandler $handler,
        CommandRepository $commandRepository
    ): void {
        $command = Command::where('idempotency_key', $this->idempotencyKey)
            ->first();

        if (!$command) {
            $command = $commandRepository->create([
                ...$this->payload,
                'idempotency_key' => $this->idempotencyKey,
            ]);
        }

        if ($command->status === EnumCommandStatus::PROCESSED) {
            return;
        }

        try {
            DB::transaction(function () use (
                $handler,
                &$command
            ) {
                $command = Command::where('idempotency_key', $this->idempotencyKey)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($command->status === EnumCommandStatus::PROCESSED) {
                    return;
                }

                $handler->handle($command);
            });
        } catch (Throwable $e) {
            $command = $command->refresh();

            if ($command->status !== EnumCommandStatus::FAILED) {
                $commandRepository->markAsFailed(
                    $command,
                    $this->resolveErrorMessage($e)
                );
            }

            throw $e;
        }
    }

    private function resolveErrorMessage(Throwable $exception): string
    {
        if ($exception instanceof DomainException) {
            return 'Erro por quebra de fluxo: ' . $exception->getMessage();
        }

        return $exception->getMessage();
    }

    /**
     * Caso o job exceda todas as tentativas
     */
    public function failed(Throwable $exception): void
    {
        $command = Command::where('idempotency_key', $this->idempotencyKey)->first();

        if ($command) {
            app(CommandRepository::class)
                ->markAsFailed(
                    $command,
                    $exception->getMessage()
                );
        }
    }
}
