<?php

declare(strict_types=1);

namespace Highfive\Inngest\Cancellation;

final class Cancellation
{
    public function __construct(
        public readonly string $id,
        public readonly ?string $environmentId,
        public readonly string $functionId,
        public readonly ?string $startedAfter,
        public readonly ?string $startedBefore,
        public readonly ?string $if,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            id: (string) ($payload['id'] ?? ''),
            environmentId: isset($payload['environment_id']) ? (string) $payload['environment_id'] : null,
            functionId: (string) ($payload['function_id'] ?? ''),
            startedAfter: isset($payload['started_after']) ? (string) $payload['started_after'] : null,
            startedBefore: isset($payload['started_before']) ? (string) $payload['started_before'] : null,
            if: isset($payload['if']) ? (string) $payload['if'] : null,
        );
    }
}
