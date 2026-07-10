<?php

namespace TriggerEngage\Laravel\Facades;

use Illuminate\Support\Facades\Facade;
use TriggerEngage\Laravel\Contracts\Dispatcher;
use TriggerEngage\Laravel\Testing\TriggerEngageFake;

/**
 * @method static void identify(string $personId, array $attributes = [])
 * @method static void event(string $name, array $data = [], ?string $person = null)
 *
 * @see \TriggerEngage\Laravel\TriggerEngageManager
 */
class TriggerEngage extends Facade
{
    public static function fake(): TriggerEngageFake
    {
        static::swap($fake = new TriggerEngageFake);

        return $fake;
    }

    protected static function getFacadeAccessor(): string
    {
        return Dispatcher::class;
    }
}
