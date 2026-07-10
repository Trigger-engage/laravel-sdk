# trigger-engage Laravel SDK

Fire events from your Laravel app into a [trigger-engage](../server) server, where
drag-and-drop automations turn them into email, SMS, and push messages.

Fail-open by design: SDK calls never throw into your application code — a
trigger-engage outage can't break your signup or payment flows.

## Install

```bash
composer require trigger-engage/laravel
php artisan vendor:publish --tag=trigger-engage-config
```

## Initialize

Initialization requires the **combination of your workspace id and an API key**
(created in the trigger-engage dashboard). Requests authenticate with HTTP Basic
auth — workspace id as username, API key as password — so a leaked key is useless
against another workspace.

```env
TRIGGER_ENGAGE_ENDPOINT=https://engage.your-domain.com
TRIGGER_ENGAGE_WORKSPACE_ID=ws_01hxyz...
TRIGGER_ENGAGE_API_KEY=te_...
```

## Usage

```php
use TriggerEngage\Laravel\Facades\TriggerEngage;

// Upsert a person profile (who messages get sent to)
TriggerEngage::identify('user-42', [
    'email' => $user->email,
    'first_name' => $user->first_name,
    'phone' => $user->phone,
    'type' => 'user',
]);

// Track an event — this is what triggers automations
TriggerEngage::event('customer_sign_up', ['plan' => 'free'], person: 'user-42');
```

Calls are queued by default (`TRIGGER_ENGAGE_DISPATCH=sync` to send inline).
Each call carries an idempotency key minted at call time, so queue retries can
never double-trigger an automation.

## Testing

```php
$fake = TriggerEngage::fake();

// ... run the code under test ...

$fake->assertEventSent('customer_sign_up', fn ($data, $person) => $person === 'user-42');
$fake->assertIdentified('user-42');
$fake->assertEventSentTimes('customer_sign_up', 1);
$fake->assertEventNotSent('wallet_funded');
$fake->assertNothingSent();
```
