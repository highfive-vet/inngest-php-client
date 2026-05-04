<?php

declare(strict_types=1);

use Highfive\Inngest\Runs\RunStatus;
use Highfive\Inngest\Tests\TestCase;

uses(TestCase::class);

it('parses runs for an event', function (): void {
    $this->queueResponse(200, [
        'data' => [
            [
                'run_id' => 'run_1',
                'status' => 'Completed',
                'output' => ['ok' => true],
                'run_started_at' => '2026-05-04T10:00:00Z',
                'ended_at' => '2026-05-04T10:00:05Z',
                'function_id' => 'app-fn',
                'event_id' => 'evt_1',
            ],
        ],
    ]);

    $client = $this->makeClient();
    $runs = $client->runs()->forEvent('evt_1');

    $request = $this->lastRequest();

    expect($request->getMethod())->toBe('GET');
    expect((string) $request->getUri())->toBe('https://api.inngest.com/v1/events/evt_1/runs');
    expect($request->getHeaderLine('Authorization'))->toBe('Bearer signkey-test-abc');

    expect($runs)->toHaveCount(1);
    expect($runs[0]->runId)->toBe('run_1');
    expect($runs[0]->status)->toBe(RunStatus::Completed);
    expect($runs[0]->output)->toBe(['ok' => true]);
});

it('exposes the cursor when listing events', function (): void {
    $this->queueResponse(200, [
        'data' => [
            ['internal_id' => 'i1', 'name' => 'a.evt', 'data' => ['x' => 1]],
        ],
        'metadata' => ['cursor' => 'next-cursor'],
    ]);

    $page = $this->makeClient()->runs()->listEvents(limit: 50, name: 'a.evt');
    $uri = (string) $this->lastRequest()->getUri();

    expect($uri)->toContain('https://api.inngest.com/v1/events?');
    expect($uri)->toContain('limit=50');
    expect($uri)->toContain('name=a.evt');

    expect($page->items)->toHaveCount(1);
    expect($page->items[0]->name)->toBe('a.evt');
    expect($page->hasMore())->toBeTrue();
    expect($page->cursor)->toBe('next-cursor');
});

it('waits for an event until all runs are terminal', function (): void {
    $this->queueResponse(200, ['data' => [['run_id' => 'r', 'status' => 'Running']]]);
    $this->queueResponse(200, ['data' => [['run_id' => 'r', 'status' => 'Completed', 'output' => 1]]]);

    $runs = $this->makeClient()->runs()->waitForEvent('evt_1', timeoutSeconds: 5, intervalMs: 1);

    expect($runs)->toHaveCount(1);
    expect($runs[0]->status)->toBe(RunStatus::Completed);
});

it('stops waiting when the timeout is reached', function (): void {
    $this->queueResponse(200, ['data' => [['run_id' => 'r', 'status' => 'Running']]]);
    $this->queueResponse(200, ['data' => [['run_id' => 'r', 'status' => 'Running']]]);
    $this->queueResponse(200, ['data' => [['run_id' => 'r', 'status' => 'Running']]]);

    $start = microtime(true);
    $runs = $this->makeClient()->runs()->waitForEvent('evt_1', timeoutSeconds: 0, intervalMs: 1);
    $elapsed = microtime(true) - $start;

    expect($elapsed)->toBeLessThan(1.0);
    expect($runs)->toHaveCount(1);
    expect($runs[0]->status)->toBe(RunStatus::Running);
});
