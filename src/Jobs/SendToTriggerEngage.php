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

    public function __construct(public array $payload) {}

    public function handle(Client $client): void
    {
        // Client performs its configured HTTP retries, then logs and swallows
        // any remaining failure so application work can stay fail-open. The
        // idempotency key remains stable throughout those attempts.
        $client->send($this->payload);
    }
}
