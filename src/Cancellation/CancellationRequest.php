<?php

declare(strict_types=1);

namespace Highfive\Inngest\Cancellation;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;

final class CancellationRequest
{
    public function __construct(
        public readonly string $appId,
        public readonly string $functionId,
        public readonly DateTimeInterface|string $startedAfter,
        public readonly DateTimeInterface|string $startedBefore,
        public readonly ?string $if = null,
    ) {
    }

    /**
     * @return array<string, string>
     */
    public function toArray(): array
    {
        $payload = [
            'app_id' => $this->appId,
            'function_id' => $this->functionId,
            'started_after' => $this->normalize($this->startedAfter),
            'started_before' => $this->normalize($this->startedBefore),
        ];
        if ($this->if !== null) {
            $payload['if'] = $this->if;
        }
        return $payload;
    }

    private function normalize(DateTimeInterface|string $value): string
    {
        if (!$value instanceof DateTimeInterface) {
            return $value;
        }

        return DateTimeImmutable::createFromInterface($value)
            ->setTimezone(new DateTimeZone('UTC'))
            ->format('Y-m-d\TH:i:s.v\Z');
    }
}
