<?php

declare(strict_types=1);

namespace Highfive\Inngest\Event;

final class SendResult
{
    /**
     * @param list<string> $ids
     */
    public function __construct(
        public readonly array $ids,
        public readonly int $status,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        $ids = $payload['ids'] ?? [];
        if (!is_array($ids)) {
            $ids = [];
        }
        $status = isset($payload['status']) && is_int($payload['status']) ? $payload['status'] : 200;
        return new self(array_values(array_map('strval', $ids)), $status);
    }
}
