<?php

namespace TriggerEngage\Laravel\Contracts;

interface Dispatcher
{
    /**
     * Upsert a person profile on the trigger-engage server.
     *
     * @param  string  $personId  Stable external identifier, e.g. "user-42".
     * @param  array<string, mixed>  $attributes  email, phone, and free-form attributes.
     * @param  string|null  $anonymousId  Prior device/session id to fold this person's
     *                                    pre-signup history into on first identify.
     */
    public function identify(string $personId, array $attributes = [], ?string $anonymousId = null): void;

    /** Merge Customer.io-style properties into an identified person. */
    public function setProperties(string $personId, array $properties): void;

    /**
     * Track a named event against a person. This is what triggers automations.
     *
     * @param  string  $name  Event name, e.g. "customer_sign_up".
     * @param  array<string, mixed>  $data  Event payload, available to templates as {{ event.* }}.
     * @param  string|null  $person  External person id. Required for automations to send anything.
     * @param  string|null  $anonymousId  Device/session id for pre-identity events, folded
     *                                     into the person on their next identify().
     */
    public function event(string $name, array $data = [], ?string $person = null, ?string $anonymousId = null): void;

    /** Add a previously identified person to a manual segment. */
    public function addToSegment(string $segmentId, string $personId): void;

    /** Remove a person from a manual segment. */
    public function removeFromSegment(string $segmentId, string $personId): void;
}
