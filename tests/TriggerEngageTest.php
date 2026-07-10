<?php

namespace TriggerEngage\Laravel\Tests;

use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use TriggerEngage\Laravel\Facades\TriggerEngage;
use TriggerEngage\Laravel\Jobs\SendToTriggerEngage;

class TriggerEngageTest extends TestCase
{
    public function test_event_dispatches_queued_job_with_idempotency_key(): void
    {
        Bus::fake();

        TriggerEngage::event('customer_sign_up', ['plan' => 'free'], person: 'user-42');

        Bus::assertDispatched(SendToTriggerEngage::class, function (SendToTriggerEngage $job) {
            return $job->payload['type'] === 'event'
                && $job->payload['name'] === 'customer_sign_up'
                && $job->payload['person_id'] === 'user-42'
                && $job->payload['data'] === ['plan' => 'free']
                && filled($job->payload['idempotency_key']);
        });
    }

    public function test_identify_dispatches_queued_job(): void
    {
        Bus::fake();

        TriggerEngage::identify('user-42', ['email' => 'ada@example.com']);

        Bus::assertDispatched(SendToTriggerEngage::class, function (SendToTriggerEngage $job) {
            return $job->payload['type'] === 'identify'
                && $job->payload['person_id'] === 'user-42'
                && $job->payload['attributes'] === ['email' => 'ada@example.com'];
        });
    }

    public function test_sync_mode_sends_http_request_with_combined_credentials(): void
    {
        config()->set('trigger-engage.dispatch', 'sync');
        Http::fake(['engage.test/*' => Http::response(['ok' => true], 202)]);

        TriggerEngage::event('customer_sign_up', ['plan' => 'free'], person: 'user-42');

        Http::assertSent(function ($request) {
            return $request->url() === 'https://engage.test/api/v1/events'
                && $request->header('Authorization')[0] === 'Basic '.base64_encode('ws_demo:te_secret')
                && $request['name'] === 'customer_sign_up'
                && $request['person_id'] === 'user-42';
        });
    }

    public function test_disabled_when_workspace_id_missing(): void
    {
        config()->set('trigger-engage.workspace_id', null);
        config()->set('trigger-engage.dispatch', 'sync');
        Bus::fake();
        Http::fake();

        TriggerEngage::event('customer_sign_up');
        TriggerEngage::identify('user-42', ['email' => 'ada@example.com']);

        Http::assertNothingSent();
        Bus::assertNotDispatched(SendToTriggerEngage::class);
    }

    public function test_http_failure_never_throws(): void
    {
        config()->set('trigger-engage.dispatch', 'sync');
        config()->set('trigger-engage.http.retries', 1);
        Http::fake(fn () => throw new \RuntimeException('connection refused'));

        TriggerEngage::event('customer_sign_up', person: 'user-42');

        $this->assertTrue(true); // reaching here means the exception was swallowed
    }

    public function test_fake_records_and_asserts(): void
    {
        $fake = TriggerEngage::fake();

        TriggerEngage::identify('user-42', ['email' => 'ada@example.com']);
        TriggerEngage::event('customer_sign_up', ['plan' => 'free'], person: 'user-42');

        $fake->assertIdentified('user-42', fn ($attrs) => $attrs['email'] === 'ada@example.com');
        $fake->assertEventSent('customer_sign_up', fn ($data, $person) => $person === 'user-42');
        $fake->assertEventSentTimes('customer_sign_up', 1);
        $fake->assertEventNotSent('wallet_funded');
    }
}
