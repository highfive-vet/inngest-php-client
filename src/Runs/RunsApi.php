<?php

declare(strict_types=1);

namespace Highfive\Inngest\Runs;

use Highfive\Inngest\Http\Transport;

final class RunsApi
{
    public function __construct(private readonly Transport $transport)
    {
    }

    /**
     * @return list<Run>
     */
    public function forEvent(string $eventId): array
    {
        $response = $this->transport->apiGet('/v1/events/' . rawurlencode($eventId) . '/runs');

        return $this->mapRuns($response['data'] ?? []);
    }

    public function get(string $runId): Run
    {
        $response = $this->transport->apiGet('/v1/runs/' . rawurlencode($runId));

        return Run::fromArray(Transport::unwrap($response));
    }

    /**
     * @return Page<EventRecord>
     */
    public function listEvents(?string $cursor = null, ?int $limit = null, ?string $name = null): Page
    {
        $response = $this->transport->apiGet('/v1/events', [
            'cursor' => $cursor,
            'limit' => $limit,
            'name' => $name,
        ]);

        $items = [];
        $raw = $response['data'] ?? [];
        if (is_array($raw)) {
            foreach ($raw as $entry) {
                if (is_array($entry)) {
                    $items[] = EventRecord::fromArray($entry);
                }
            }
        }
        $nextCursor = null;
        if (isset($response['metadata']) && is_array($response['metadata'])) {
            $nextCursor = isset($response['metadata']['cursor']) ? (string) $response['metadata']['cursor'] : null;
            if ($nextCursor === '') {
                $nextCursor = null;
            }
        }
        return new Page($items, $nextCursor);
    }

    public function getEvent(string $eventId): EventRecord
    {
        $response = $this->transport->apiGet('/v1/events/' . rawurlencode($eventId));

        return EventRecord::fromArray(Transport::unwrap($response));
    }

    /**
     * Polls runs for an event until every run reaches a terminal status, or the timeout elapses.
     *
     * @return list<Run>
     */
    public function waitForEvent(string $eventId, int $timeoutSeconds = 60, int $intervalMs = 1000): array
    {
        $deadline = microtime(true) + $timeoutSeconds;
        $runs = [];

        while (true) {
            $runs = $this->forEvent($eventId);
            if ($runs !== [] && $this->allTerminal($runs)) {
                return $runs;
            }
            $remainingMs = (int) (($deadline - microtime(true)) * 1000);
            if ($remainingMs <= 0) {
                return $runs;
            }
            usleep(min(max(1, $intervalMs), $remainingMs) * 1000);
        }
    }

    /**
     * @param list<Run> $runs
     */
    private function allTerminal(array $runs): bool
    {
        foreach ($runs as $run) {
            if (!$run->status->isTerminal()) {
                return false;
            }
        }
        return true;
    }

    /**
     * @return list<Run>
     */
    private function mapRuns(mixed $raw): array
    {
        if (!is_array($raw)) {
            return [];
        }
        $runs = [];
        foreach ($raw as $entry) {
            if (is_array($entry)) {
                $runs[] = Run::fromArray($entry);
            }
        }
        return $runs;
    }
}
