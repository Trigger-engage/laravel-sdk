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

    /** @var array<int, array{action: string, segment_id: string, person_id: string}> */
    protected array $segmentChanges = [];

    public function identify(string $personId, array $attributes = [], ?string $anonymousId = null): void
    {
        $this->identifies[] = ['person_id' => $personId, 'attributes' => $attributes, 'anonymous_id' => $anonymousId];
    }

    public function setProperties(string $personId, array $properties): void
    {
        $this->identifies[] = ['person_id' => $personId, 'attributes' => $properties];
    }

    public function assertPropertiesSet(string $personId, ?Closure $callback = null): void
    {
        $this->assertIdentified($personId, $callback);
    }

    public function event(string $name, array $data = [], ?string $person = null, ?string $anonymousId = null): void
    {
        $this->events[] = ['name' => $name, 'data' => $data, 'person' => $person, 'anonymous_id' => $anonymousId];
    }

    public function addToSegment(string $segmentId, string $personId): void
    {
        $this->segmentChanges[] = ['action' => 'add', 'segment_id' => $segmentId, 'person_id' => $personId];
    }

    public function removeFromSegment(string $segmentId, string $personId): void
    {
        $this->segmentChanges[] = ['action' => 'remove', 'segment_id' => $segmentId, 'person_id' => $personId];
    }

    public function assertAddedToSegment(string $segmentId, string $personId): void
    {
        Assert::assertContains(['action' => 'add', 'segment_id' => $segmentId, 'person_id' => $personId], $this->segmentChanges);
    }

    public function assertRemovedFromSegment(string $segmentId, string $personId): void
    {
        Assert::assertContains(['action' => 'remove', 'segment_id' => $segmentId, 'person_id' => $personId], $this->segmentChanges);
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
        Assert::assertEmpty($this->segmentChanges, 'Segment memberships were changed unexpectedly.');
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
