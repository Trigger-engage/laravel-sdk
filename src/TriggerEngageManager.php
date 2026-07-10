<?php

namespace TriggerEngage\Laravel;

use Illuminate\Support\Str;
use TriggerEngage\Laravel\Contracts\Dispatcher;
use TriggerEngage\Laravel\Jobs\SendToTriggerEngage;

class TriggerEngageManager implements Dispatcher
{
    public function __construct(protected array $config)
    {
    }

    public function identify(string $personId, array $attributes = []): void
    {
        $this->dispatch([
            'type' => 'identify',
            'person_id' => $personId,
            'attributes' => $attributes,
            'idempotency_key' => (string) Str::ulid(),
            'occurred_at' => now()->toIso8601String(),
        ]);
    }

    public function event(string $name, array $data = [], ?string $person = null): void
    {
        $this->dispatch([
            'type' => 'event',
            'name' => $name,
            'person_id' => $person,
            'data' => $data,
            'idempotency_key' => (string) Str::ulid(),
            'occurred_at' => now()->toIso8601String(),
        ]);
    }

    public function enabled(): bool
    {
        return (bool) ($this->config['enabled'] ?? false)
            && filled($this->config['endpoint'] ?? null)
            && filled($this->config['workspace_id'] ?? null)
            && filled($this->config['api_key'] ?? null);
    }

    /**
     * The idempotency key is minted here, at call time, so queue retries of
     * the same job can never register as two distinct occurrences server-side.
     */
    protected function dispatch(array $payload): void
    {
        if (! $this->enabled()) {
            return;
        }

        if (($this->config['dispatch'] ?? 'queue') === 'sync') {
            app(Client::class)->send($payload);

            return;
        }

        $job = new SendToTriggerEngage($payload);

        if ($connection = $this->config['queue']['connection'] ?? null) {
            $job->onConnection($connection);
        }

        if ($queue = $this->config['queue']['name'] ?? null) {
            $job->onQueue($queue);
        }

        dispatch($job);
    }
}
