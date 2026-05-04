<?php

declare(strict_types=1);

use Highfive\Inngest\Event\Event;
use Highfive\Inngest\Event\DispatchableEvent;
use Highfive\Inngest\Exception\AuthenticationException;
use Highfive\Inngest\Exception\RateLimitException;
use Highfive\Inngest\Exception\ServerException;
use Highfive\Inngest\Tests\TestCase;

uses(TestCase::class);

it('sends a single event to inn gs', function (): void {
    $this->queueResponse(200, ['ids' => ['01H08W4TMBNKMEWFD0TYC532GG'], 'status' => 200]);

    $client = $this->makeClient(eventKey: 'ek-123');
    $result = $client->events()->send(new Event(name: 'user.signup', data: ['userId' => 'u1']));

    $request = $this->lastRequest();

    expect($request->getMethod())->toBe('POST');
    expect((string) $request->getUri())->toBe('https://inn.gs/e/ek-123');
    expect($request->getHeaderLine('Content-Type'))->toBe('application/json');

    $body = json_decode((string) $request->getBody(), true);

    expect($body['name'])->toBe('user.signup');
    expect($body['data'])->toBe(['userId' => 'u1']);

    expect($result->ids)->toBe(['01H08W4TMBNKMEWFD0TYC532GG']);
    expect($result->status)->toBe(200);
});

it('sends a batch as an array', function (): void {
    $this->queueResponse(200, ['ids' => ['a', 'b'], 'status' => 200]);

    $client = $this->makeClient(eventKey: 'ek-123');
    $result = $client->events()->sendMany([
        new Event(name: 'a.evt', data: ['n' => 1]),
        new Event(name: 'b.evt', data: ['n' => 2], id: 'dedupe-key'),
    ]);

    $body = json_decode((string) $this->lastRequest()->getBody(), true);

    expect($body)->toHaveCount(2);
    expect($body[0]['name'])->toBe('a.evt');
    expect($body[1]['id'])->toBe('dedupe-key');
    expect($result->ids)->toBe(['a', 'b']);
});

it('sends a typed dispatchable event', function (): void {
    $this->queueResponse(200, ['ids' => ['evt_1'], 'status' => 200]);

    $client = $this->makeClient(eventKey: 'ek-123');
    $result = $client->events()->send(new SignupEvent('u1'));

    $body = json_decode((string) $this->lastRequest()->getBody(), true);

    expect($body['name'])->toBe('user.signup');
    expect($body['data'])->toBe(['userId' => 'u1']);
    expect($result->ids)->toBe(['evt_1']);
});

it('sends a mixed batch of raw and typed events', function (): void {
    $this->queueResponse(200, ['ids' => ['a', 'b'], 'status' => 200]);

    $client = $this->makeClient(eventKey: 'ek-123');
    $client->events()->sendMany([
        new Event(name: 'a.evt', data: ['n' => 1]),
        new SignupEvent('u2'),
    ]);

    $body = json_decode((string) $this->lastRequest()->getBody(), true);

    expect($body[0]['name'])->toBe('a.evt');
    expect($body[1]['name'])->toBe('user.signup');
    expect($body[1]['data'])->toBe(['userId' => 'u2']);
});

it('forwards the branch env header', function (): void {
    $this->queueResponse(200, ['ids' => ['x'], 'status' => 200]);

    $client = $this->makeClient(eventKey: 'ek', env: 'feature-branch');
    $client->events()->send(new Event(name: 'a'));

    expect($this->lastRequest()->getHeaderLine('x-inngest-env'))->toBe('feature-branch');
});

it('uses the dev server url for ingestion when configured', function (): void {
    $this->queueResponse(200, ['ids' => ['x'], 'status' => 200]);

    $client = $this->makeClient(eventKey: 'fake', devServerUrl: 'http://localhost:8288');
    $client->events()->send(new Event(name: 'a'));

    expect((string) $this->lastRequest()->getUri())->toBe('http://localhost:8288/e/fake');
});

it('maps auth failures', function (): void {
    $this->queueResponse(401, ['error' => 'invalid event key']);

    expect(fn () => $this->makeClient(eventKey: 'bad')->events()->send(new Event(name: 'a')))
        ->toThrow(AuthenticationException::class);
});

it('maps rate limits', function (): void {
    $this->queueResponse(429, ['error' => 'too many requests']);

    expect(fn () => $this->makeClient(eventKey: 'ek')->events()->send(new Event(name: 'a')))
        ->toThrow(RateLimitException::class);
});

it('maps server errors', function (): void {
    $this->queueResponse(503, '');

    expect(fn () => $this->makeClient(eventKey: 'ek')->events()->send(new Event(name: 'a')))
        ->toThrow(ServerException::class);
});

final readonly class SignupEvent implements DispatchableEvent
{
    public function __construct(private string $userId) {}

    public function toInngestEvent(): Event
    {
        return new Event(
            name: 'user.signup',
            data: ['userId' => $this->userId],
        );
    }
}
