<?php

namespace TriggerEngage\Laravel\Contracts;

interface Dispatcher
{
    /**
     * Upsert a person profile on the trigger-engage server.
     *
     * @param  string  $personId  Stable external identifier, e.g. "user-42".
     * @param  array<string, mixed>  $attributes  email, phone, and free-form attributes.
     */
    public function identify(string $personId, array $attributes = []): void;

    /**
     * Track a named event against a person. This is what triggers automations.
     *
     * @param  string  $name  Event name, e.g. "customer_sign_up".
     * @param  array<string, mixed>  $data  Event payload, available to templates as {{ event.* }}.
     * @param  string|null  $person  External person id. Required for automations to send anything.
     */
    public function event(string $name, array $data = [], ?string $person = null): void;
}
