<?php

declare(strict_types=1);

namespace Highfive\Inngest\Runs;

final class Run
{
    public function __construct(
        public readonly string $runId,
        public readonly RunStatus $status,
        public readonly mixed $output,
        public readonly ?string $startedAt,
        public readonly ?string $endedAt,
        public readonly ?string $functionId,
        public readonly ?string $functionVersion,
        public readonly ?string $eventId,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            runId: (string) ($payload['run_id'] ?? ''),
            status: RunStatus::fromString((string) ($payload['status'] ?? '')),
            output: $payload['output'] ?? null,
            startedAt: isset($payload['run_started_at']) ? (string) $payload['run_started_at'] : (isset($payload['started_at']) ? (string) $payload['started_at'] : null),
            endedAt: isset($payload['ended_at']) ? (string) $payload['ended_at'] : null,
            functionId: isset($payload['function_id']) ? (string) $payload['function_id'] : null,
            functionVersion: isset($payload['function_version']) ? (string) $payload['function_version'] : null,
            eventId: isset($payload['event_id']) ? (string) $payload['event_id'] : null,
        );
    }
}
