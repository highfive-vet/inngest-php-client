# Inngest PHP Client

A framework-agnostic PHP **API client** for [Inngest](https://www.inngest.com).

It is **not** an SDK: PHP is never an Inngest runtime. The package only sends events and manages runs from outside Inngest, by talking to two HTTP surfaces:

- the event ingestion endpoint (`https://inn.gs`)
- the v1 REST API (`https://api.inngest.com/v1`) for runs, events, and bulk cancellations

If you want PHP to author and serve Inngest functions, you want a different library — Inngest's own SDKs (TypeScript, Python, Go) embed a function runtime and signature-verifying serve handler that this package intentionally does not.

## Requirements

- PHP 8.3+
- A PSR-18 HTTP client and PSR-17 factories — auto-discovered via `php-http/discovery`. In a Laravel app this picks up Guzzle automatically; otherwise install any combination such as `nyholm/psr7` + `symfony/http-client` or `guzzlehttp/guzzle` + `guzzlehttp/psr7`.

## Install

```bash
composer require highfive/inngest-php
```

## Quick start

```php
use DateTimeImmutable;
use Highfive\Inngest\Client;
use Highfive\Inngest\Event\Event;
use Highfive\Inngest\Cancellation\CancellationRequest;

$client = Client::create(
    eventKey: getenv('INNGEST_EVENT_KEY'),
    signingKey: getenv('INNGEST_SIGNING_KEY'),
);

// 1. Send an event (triggers any function listening for "user.signup")
$result = $client->events()->send(new Event(
    name: 'user.signup',
    data: ['userId' => 'u_123'],
));
$eventId = $result->ids[0];

// 2. Inspect the resulting run(s)
$runs = $client->runs()->forEvent($eventId);

// 3. Or block until the run finishes
$runs = $client->runs()->waitForEvent($eventId, timeoutSeconds: 60);

// 4. Cancel runs in bulk
$client->cancellations()->bulk(new CancellationRequest(
    appId: 'acme-app',
    functionId: 'schedule-reminder',
    startedAfter: new DateTimeImmutable('-1 hour'),
    startedBefore: new DateTimeImmutable('now'),
    if: "event.data.userId == 'u_123'",
));
```

## Typed consumer events

If you want typed events in your app code, implement `DispatchableEvent` and convert your DTO into the low-level `Event` payload the client sends:

```php
use Highfive\Inngest\Event\DispatchableEvent;
use Highfive\Inngest\Event\Event;

final readonly class UserSignedUp implements DispatchableEvent
{
    public function __construct(
        public string $userId,
        public string $email,
    ) {}

    public function toInngestEvent(): Event
    {
        return new Event(
            name: 'user.signup',
            data: [
                'userId' => $this->userId,
                'email' => $this->email,
            ],
        );
    }
}

$client->events()->send(new UserSignedUp('u_123', 'jane@example.com'));
```

`sendMany()` also accepts a mix of raw `Event` instances and typed `DispatchableEvent` objects.

## Branch environments

```php
$client = Client::create(eventKey: '...', signingKey: '...', env: 'feature/checkout');
```

The `env` is forwarded as `x-inngest-env` on every request.

## Local dev server

Point both base URLs at Inngest's local dev server:

```php
$client = Client::create(eventKey: 'fake', devServerUrl: 'http://localhost:8288');
```

## Laravel

If you install this package in a Laravel app (11.x or 12.x), the bundled `InngestServiceProvider` is auto-discovered. There is nothing else to install — the `Highfive\Inngest\Laravel` namespace is dormant outside Laravel because its classes extend framework types that only exist when Laravel is present.

Configure with environment variables:

```env
INNGEST_EVENT_KEY=...
INNGEST_SIGNING_KEY=signkey-prod-...
INNGEST_ENV=feature/checkout       # optional, branch envs
INNGEST_DEV_SERVER_URL=             # optional, e.g. http://localhost:8288 in dev
```

Laravel defaults to the production Inngest base URLs when you do not override them:

- `event_base_url`: `https://inn.gs`
- `api_base_url`: `https://api.inngest.com`

Optionally publish the config:

```bash
php artisan vendor:publish --tag=inngest-config
```

Resolve via DI, the container alias, or the `Inngest` facade:

```php
use Highfive\Inngest\Client;
use Highfive\Inngest\Event\Event;
use Inngest; // Facade

class CheckoutController
{
    public function __invoke(Client $inngest)
    {
        $inngest->events()->send(new Event('order.placed', ['orderId' => 42]));
        // or:
        Inngest::events()->send(new Event('order.placed', ['orderId' => 42]));
    }
}
```

Sub-API services (`EventApi`, `RunsApi`, `CancellationApi`) are also bound and can be injected directly.

## Errors

All errors extend `Highfive\Inngest\Exception\InngestException`:

- `AuthenticationException` — 401/403
- `BadRequestException` — 400
- `NotFoundException` — 404
- `RateLimitException` — 429
- `ServerException` — 5xx
- `TransportException` — network / PSR-18 client failures
