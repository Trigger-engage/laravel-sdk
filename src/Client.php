<?php

namespace TriggerEngage\Laravel;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * HTTP transport. Fail-open by design: a trigger-engage outage must never
 * surface as an exception inside application code (signup, payments, ...).
 */
class Client
{
    public function __construct(protected array $config) {}

    public function send(array $payload): void
    {
        try {
            $response = match ($payload['type']) {
                'identify' => $this->request()->put(
                    '/api/v1/people/'.rawurlencode($payload['person_id']),
                    array_filter([
                        'attributes' => $payload['attributes'],
                        'anonymous_id' => $payload['anonymous_id'] ?? null,
                        'idempotency_key' => $payload['idempotency_key'],
                    ], fn ($value) => $value !== null)
                ),
                'properties' => $this->request()->patch(
                    '/api/v1/people/'.rawurlencode($payload['person_id']).'/properties',
                    ['properties' => $payload['properties']]
                ),
                'event' => $this->request()->post('/api/v1/events', array_filter([
                    'name' => $payload['name'],
                    'person_id' => $payload['person_id'],
                    'anonymous_id' => $payload['anonymous_id'] ?? null,
                    'data' => $payload['data'],
                    'idempotency_key' => $payload['idempotency_key'],
                    'occurred_at' => $payload['occurred_at'],
                ], fn ($value) => $value !== null)),
                'segment_add' => $this->request()->put('/api/v1/segments/'.rawurlencode($payload['segment_id']).'/people/'.rawurlencode($payload['person_id'])),
                'segment_remove' => $this->request()->delete('/api/v1/segments/'.rawurlencode($payload['segment_id']).'/people/'.rawurlencode($payload['person_id'])),
                default => null,
            };

            if ($response && $response->failed()) {
                Log::warning('trigger-engage: request failed', [
                    'type' => $payload['type'],
                    'status' => $response->status(),
                    'body' => $response->json(),
                ]);
            }
        } catch (\Throwable $exception) {
            Log::warning('trigger-engage: request exception', [
                'type' => $payload['type'],
                'message' => $exception->getMessage(),
            ]);
        }
    }

    protected function request(): PendingRequest
    {
        // Credentials are the combination of workspace id + API key: the
        // server only accepts a key that belongs to that exact workspace.
        return Http::baseUrl(rtrim($this->config['endpoint'], '/'))
            ->withBasicAuth($this->config['workspace_id'], $this->config['api_key'])
            ->timeout((int) ($this->config['http']['timeout'] ?? 10))
            ->retry(
                (int) ($this->config['http']['retries'] ?? 3),
                200,
                throw: false
            )
            ->acceptJson()
            ->asJson();
    }
}
