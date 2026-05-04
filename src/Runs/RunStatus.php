<?php

declare(strict_types=1);

namespace Highfive\Inngest\Runs;

enum RunStatus: string
{
    case Running = 'Running';
    case Completed = 'Completed';
    case Failed = 'Failed';
    case Cancelled = 'Cancelled';
    case Unknown = 'Unknown';

    public static function fromString(string $value): self
    {
        return match (strtolower($value)) {
            'running' => self::Running,
            'completed' => self::Completed,
            'failed' => self::Failed,
            'cancelled', 'canceled' => self::Cancelled,
            default => self::Unknown,
        };
    }

    public function isTerminal(): bool
    {
        return match ($this) {
            self::Completed, self::Failed, self::Cancelled => true,
            default => false,
        };
    }
}
