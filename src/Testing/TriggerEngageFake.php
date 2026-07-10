<?php

namespace TriggerEngage\Laravel\Testing;

use Closure;
use PHPUnit\Framework\Assert;
use TriggerEngage\Laravel\Contracts\Dispatcher;

class TriggerEngageFake implements Dispatcher
{
    /** @var array<int, array{name: string, data: array, person: string|null}> */
    protected array $events = [];

    /** @var array<int, array{person_id: string, attributes: array}> */
    protected array $identifies = [];

    public function identify(string $personId, array $attributes = []): void
    {
        $this->identifies[] = ['person_id' => $personId, 'attributes' => $attributes];
    }

    public function event(string $name, array $data = [], ?string $person = null): void
    {
        $this->events[] = ['name' => $name, 'data' => $data, 'person' => $person];
    }

    public function assertEventSent(string $name, ?Closure $callback = null): void
    {
        $matching = $this->sentEvents($name, $callback);

        Assert::assertNotEmpty(
            $matching,
            "Expected event [{$name}] was not sent".($callback ? ' matching the given callback.' : '.')
        );
    }

    public function assertEventNotSent(string $name, ?Closure $callback = null): void
    {
        Assert::assertEmpty(
            $this->sentEvents($name, $callback),
            "Unexpected event [{$name}] was sent."
        );
    }

    public function assertEventSentTimes(string $name, int $times): void
    {
        $count = count($this->sentEvents($name));

        Assert::assertSame(
            $times,
            $count,
            "Expected event [{$name}] to be sent {$times} times, sent {$count} times."
        );
    }

    public function assertIdentified(string $personId, ?Closure $callback = null): void
    {
        $matching = array_filter(
            $this->identifies,
            fn (array $call) => $call['person_id'] === $personId
                && (! $callback || $callback($call['attributes']))
        );

        Assert::assertNotEmpty($matching, "Expected person [{$personId}] was not identified.");
    }

    public function assertNothingSent(): void
    {
        Assert::assertEmpty($this->events, 'Events were sent unexpectedly.');
        Assert::assertEmpty($this->identifies, 'People were identified unexpectedly.');
    }

    /** @return array<int, array{name: string, data: array, person: string|null}> */
    public function sentEvents(?string $name = null, ?Closure $callback = null): array
    {
        return array_values(array_filter(
            $this->events,
            fn (array $event) => (! $name || $event['name'] === $name)
                && (! $callback || $callback($event['data'], $event['person']))
        ));
    }

    /** @return array<int, array{person_id: string, attributes: array}> */
    public function identified(): array
    {
        return $this->identifies;
    }
}
