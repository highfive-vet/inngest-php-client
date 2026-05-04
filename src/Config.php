<?php

declare(strict_types=1);

namespace Highfive\Inngest;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

final class Config
{
    public const DEFAULT_EVENT_BASE_URL = 'https://inn.gs';
    public const DEFAULT_API_BASE_URL = 'https://api.inngest.com';
    public const DEFAULT_DEV_SERVER_URL = 'http://localhost:8288';

    public readonly string $eventBaseUrl;
    public readonly string $apiBaseUrl;

    public function __construct(
        public readonly ?string $eventKey,
        public readonly ?string $signingKey,
        public readonly ClientInterface $httpClient,
        public readonly RequestFactoryInterface $requestFactory,
        public readonly StreamFactoryInterface $streamFactory,
        public readonly ?string $env = null,
        ?string $eventBaseUrl = null,
        ?string $apiBaseUrl = null,
        ?string $devServerUrl = null,
    ) {
        if ($devServerUrl !== null) {
            $base = rtrim($devServerUrl, '/');
            $this->eventBaseUrl = $base;
            $this->apiBaseUrl = $base;
        } else {
            $this->eventBaseUrl = rtrim($eventBaseUrl ?? self::DEFAULT_EVENT_BASE_URL, '/');
            $this->apiBaseUrl = rtrim($apiBaseUrl ?? self::DEFAULT_API_BASE_URL, '/');
        }
    }
}
