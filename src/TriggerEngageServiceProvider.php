<?php

namespace TriggerEngage\Laravel;

use Illuminate\Support\ServiceProvider;
use TriggerEngage\Laravel\Contracts\Dispatcher;

class TriggerEngageServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/trigger-engage.php', 'trigger-engage');

        $this->app->singleton(Dispatcher::class, function ($app) {
            return new TriggerEngageManager($app['config']->get('trigger-engage', []));
        });

        $this->app->singleton(Client::class, function ($app) {
            return new Client($app['config']->get('trigger-engage', []));
        });
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/trigger-engage.php' => config_path('trigger-engage.php'),
            ], 'trigger-engage-config');
        }
    }
}
