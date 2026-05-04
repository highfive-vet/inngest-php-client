<?php

declare(strict_types=1);

namespace Highfive\Inngest\Runs;

final class EventRecord
{
    /**
     * @param array<string, mixed> $data
     */
    public function __construct(
        public readonly string $internalId,
        public readonly string $name,
        public readonly array $data,
        public readonly ?string $receivedAt,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        $data = $payload['data'] ?? [];
        if (!is_array($data)) {
            $data = [];
        }
        return new self(
            internalId: (string) ($payload['internal_id'] ?? $payload['id'] ?? ''),
            name: (string) ($payload['name'] ?? ''),
            data: $data,
            receivedAt: isset($payload['received_at']) ? (string) $payload['received_at'] : null,
        );
    }
}
