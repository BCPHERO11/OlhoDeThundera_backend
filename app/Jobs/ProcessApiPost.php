<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessApiPost implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $payload;

    public function __construct(array $payload)
    {
        $this->payload = $payload;
    }

    public function handle()
    {
        $command = $this->payload;
        dd($command, 'Chegou aqui!');
    }

    public function failed()
    {

    }
}
