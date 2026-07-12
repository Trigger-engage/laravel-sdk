<?php

namespace TriggerEngage\Laravel;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use TriggerEngage\Laravel\Contracts\Dispatcher;
use TriggerEngage\Laravel\Jobs\SendToTriggerEngage;

class TriggerEngageManager implements Dispatcher
{
    public function __construct(protected array $config) {}

    public function identify(string $personId, array $attributes = [], ?string $anonymousId = null): void
    {
        $this->dispatch([
            'type' => 'identify',
            'person_id' => $personId,
            'attributes' => $attributes,
            'anonymous_id' => $anonymousId,
            'idempotency_key' => (string) Str::ulid(),
            'occurred_at' => now()->toIso8601String(),
        ]);
    }

    public function setProperties(string $personId, array $properties): void
    {
        $this->dispatch([
            'type' => 'properties',
            'person_id' => $personId,
            'properties' => $properties,
            'idempotency_key' => (string) Str::ulid(),
            'occurred_at' => now()->toIso8601String(),
        ]);
    }

    public function event(string $name, array $data = [], ?string $person = null, ?string $anonymousId = null): void
    {
        if (blank($person) && blank($anonymousId)) {
            if ($this->enabled()) {
                Log::warning('trigger-engage: event skipped because a person id or anonymous id is required', [
                    'event' => $name,
                ]);
            }

            return;
        }

        $this->dispatch([
            'type' => 'event',
            'name' => $name,
            'person_id' => $person,
            'anonymous_id' => $anonymousId,
            'data' => $data,
            'idempotency_key' => (string) Str::ulid(),
            'occurred_at' => now()->toIso8601String(),
        ]);
    }

    public function addToSegment(string $segmentId, string $personId): void
    {
        $this->dispatch(['type' => 'segment_add', 'segment_id' => $segmentId, 'person_id' => $personId]);
    }

    public function removeFromSegment(string $segmentId, string $personId): void
    {
        $this->dispatch(['type' => 'segment_remove', 'segment_id' => $segmentId, 'person_id' => $personId]);
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
