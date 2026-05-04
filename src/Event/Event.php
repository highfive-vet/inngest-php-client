<?php

declare(strict_types=1);

namespace Highfive\Inngest\Event;

final class Event implements DispatchableEvent
{
    /**
     * @param array<string, mixed> $data
     * @param array<string, mixed>|null $user
     */
    public function __construct(
        public readonly string $name,
        public readonly array $data = [],
        public readonly ?array $user = null,
        public readonly ?int $ts = null,
        public readonly ?string $id = null,
        public readonly ?string $v = null,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $out = ['name' => $this->name, 'data' => (object) $this->data];
        if ($this->user !== null) {
            $out['user'] = (object) $this->user;
        }
        if ($this->ts !== null) {
            $out['ts'] = $this->ts;
        }
        if ($this->id !== null) {
            $out['id'] = $this->id;
        }
        if ($this->v !== null) {
            $out['v'] = $this->v;
        }
        return $out;
    }

    public function toInngestEvent(): self
    {
        return $this;
    }
}
