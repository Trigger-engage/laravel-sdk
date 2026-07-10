<?php

namespace TriggerEngage\Laravel\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use TriggerEngage\Laravel\TriggerEngageServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [TriggerEngageServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('trigger-engage', [
            'enabled' => true,
            'endpoint' => 'https://engage.test',
            'workspace_id' => 'ws_demo',
            'api_key' => 'te_secret',
            'dispatch' => 'queue',
            'queue' => ['connection' => null, 'name' => 'default'],
            'http' => ['timeout' => 10, 'retries' => 3],
        ]);
    }
}
