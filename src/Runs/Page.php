<?php

declare(strict_types=1);

namespace Highfive\Inngest\Runs;

/**
 * @template T
 */
final class Page
{
    /**
     * @param list<T> $items
     */
    public function __construct(
        public readonly array $items,
        public readonly ?string $cursor,
    ) {
    }

    public function hasMore(): bool
    {
        return $this->cursor !== null && $this->cursor !== '';
    }
}
