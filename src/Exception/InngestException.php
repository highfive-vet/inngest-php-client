<?php

declare(strict_types=1);

namespace Highfive\Inngest\Exception;

use RuntimeException;
use Throwable;

class InngestException extends RuntimeException
{
    public function __construct(
        string $message = '',
        public readonly ?int $statusCode = null,
        public readonly ?string $responseBody = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
