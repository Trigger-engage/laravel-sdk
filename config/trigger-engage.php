<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Enabled
    |--------------------------------------------------------------------------
    |
    | When disabled (or when the endpoint/key are missing) every SDK call is a
    | silent no-op. The SDK never throws into application code either way.
    |
    */

    'enabled' => env('TRIGGER_ENGAGE_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Server
    |--------------------------------------------------------------------------
    |
    | Base URL of your trigger-engage server (no trailing slash, no /api/v1),
    | plus the workspace id and an API key generated in the trigger-engage
    | dashboard. Requests authenticate with the combination of both: the
    | workspace id is the Basic-auth username, the API key the password.
    |
    */

    'endpoint' => env('TRIGGER_ENGAGE_ENDPOINT'),

    'workspace_id' => env('TRIGGER_ENGAGE_WORKSPACE_ID'),

    'api_key' => env('TRIGGER_ENGAGE_API_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Dispatch mode
    |--------------------------------------------------------------------------
    |
    | "queue" (default) pushes a job so the HTTP call happens off-request.
    | "sync" sends inline — useful locally or in queue-less environments.
    |
    */

    'dispatch' => env('TRIGGER_ENGAGE_DISPATCH', 'queue'),

    'queue' => [
        'connection' => env('TRIGGER_ENGAGE_QUEUE_CONNECTION'),
        'name' => env('TRIGGER_ENGAGE_QUEUE', 'default'),
    ],

    'http' => [
        'timeout' => env('TRIGGER_ENGAGE_TIMEOUT', 10),
        'retries' => env('TRIGGER_ENGAGE_RETRIES', 3),
    ],

];
