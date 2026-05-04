<?php

declare(strict_types=1);

use Highfive\Inngest\Cancellation\CancellationRequest;
use Highfive\Inngest\Tests\TestCase;

uses(TestCase::class);

it('builds the expected bulk cancellation request shape', function (): void {
    $this->queueResponse(200, [
        'id' => '01HMRMPE5ZQ4AMNJ3S2N79QGRZ',
        'environment_id' => 'env-1',
        'function_id' => 'schedule-reminder',
        'started_after' => '2026-01-21T18:23:12.000Z',
        'started_before' => '2026-01-22T14:22:42.130Z',
        'if' => "event.data.userId == 'u1'",
    ]);

    $request = new CancellationRequest(
        appId: 'acme-app',
        functionId: 'schedule-reminder',
        startedAfter: new DateTimeImmutable('2026-01-21T18:23:12+00:00'),
        startedBefore: new DateTimeImmutable('2026-01-22T14:22:42+00:00'),
        if: "event.data.userId == 'u1'",
    );

    $cancellation = $this->makeClient()->cancellations()->bulk($request);

    $http = $this->lastRequest();

    expect($http->getMethod())->toBe('POST');
    expect((string) $http->getUri())->toBe('https://api.inngest.com/v1/cancellations');
    expect($http->getHeaderLine('Authorization'))->toBe('Bearer signkey-test-abc');

    $body = json_decode((string) $http->getBody(), true);

    expect($body['app_id'])->toBe('acme-app');
    expect($body['function_id'])->toBe('schedule-reminder');
    expect($body['if'])->toBe("event.data.userId == 'u1'");
    expect($body['started_after'])->toMatch('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\.\d{3}Z$/');

    expect($cancellation->id)->toBe('01HMRMPE5ZQ4AMNJ3S2N79QGRZ');
    expect($cancellation->functionId)->toBe('schedule-reminder');
});

it('normalizes date objects to utc before serializing cancellation windows', function (): void {
    $payload = (new CancellationRequest(
        appId: 'acme-app',
        functionId: 'schedule-reminder',
        startedAfter: new DateTimeImmutable('2026-01-21 20:23:12.456', new DateTimeZone('Europe/Paris')),
        startedBefore: new DateTimeImmutable('2026-01-22 16:22:42.130', new DateTimeZone('Europe/Paris')),
    ))->toArray();

    expect($payload['started_after'])->toBe('2026-01-21T19:23:12.456Z');
    expect($payload['started_before'])->toBe('2026-01-22T15:22:42.130Z');
});
