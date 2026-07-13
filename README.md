# trigger-engage Laravel SDK

Fire events from your Laravel app into a [trigger-engage](../server) server, where
drag-and-drop automations turn them into email, SMS, and push messages.

Fail-open by design: SDK calls never throw into your application code — a
trigger-engage outage can't break your signup or payment flows.

Part of **[TriggerEngage](https://triggerengage.com)**, open-source lifecycle messaging for
Laravel. New to event-driven messaging? Start with [how to send event-based emails in
Laravel](https://triggerengage.com/blog/event-based-emails-laravel.html).

## Install

```bash
composer require trigger-engage/laravel
php artisan vendor:publish --tag=trigger-engage-config
```

If the complete `trigger-engage/server` package is installed in the same
Laravel application, it provides this SDK automatically and replaces the HTTP
transport with an in-process dispatcher. No endpoint or API credentials are
needed in that embedded mode.

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

// Merge typed profile properties (numbers, booleans, strings, arrays, or objects)
TriggerEngage::setProperties('user-42', [
    'appointments' => 4,
    'plan' => 'wellness',
    'active' => true,
]);

// Track an event — this is what triggers automations
TriggerEngage::event('customer_sign_up', ['plan' => 'free'], person: 'user-42');

// Add/remove a person to a MANUAL segment (event-driven and rule-based
// segments manage their own membership and are not changed from the SDK)
TriggerEngage::addToSegment('seg_01...', 'user-42');
TriggerEngage::removeFromSegment('seg_01...', 'user-42');
```

### Anonymous → identified

Track people before you know who they are with an `anonymous_id`, then merge that
history when they sign up. See the [anonymous identity guide](../server/docs/guides/anonymous-identity.md).

```php
// Before signup — an anonymous event keyed by a device/session id
TriggerEngage::event('pricing_view', ['path' => '/pricing'], anonymousId: 'device-abc');

// On signup — folds the anonymous profile (events, attributes, memberships) into user-42
TriggerEngage::identify('user-42', ['email' => $user->email], anonymousId: 'device-abc');
```

Both parameters are optional and backward compatible. An event needs **either** a
`person`/`personId` **or** an `anonymousId`; if both are missing, the fail-open SDK logs and
skips the call instead of sending an unusable event or throwing.

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

## Learn more

- **Website:** [triggerengage.com](https://triggerengage.com)
- **Guides:** [event-based emails in Laravel](https://triggerengage.com/blog/event-based-emails-laravel.html) · [building a notification system in Laravel](https://triggerengage.com/blog/notification-system-laravel.html)
