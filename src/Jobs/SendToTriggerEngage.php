<?php

namespace TriggerEngage\Laravel\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use TriggerEngage\Laravel\Client;

class SendToTriggerEngage implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [10, 60];

    public function __construct(public array $payload)
    {
    }

    public function handle(Client $client): void
    {
        // Client is fail-open (logs and swallows), so job retries only cover
        // transient container/DNS-level faults. The idempotency key travels
        // with the payload, so a retry can never double-register server-side.
        $client->send($this->payload);
    }
}
