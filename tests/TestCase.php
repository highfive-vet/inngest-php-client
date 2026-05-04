<?php

declare(strict_types=1);

namespace Highfive\Inngest\Tests;

use Highfive\Inngest\Client;
use Highfive\Inngest\Config;
use Http\Mock\Client as MockHttpClient;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response;
use PHPUnit\Framework\TestCase as BaseTestCase;
use Psr\Http\Message\RequestInterface;

abstract class TestCase extends BaseTestCase
{
    protected MockHttpClient $http;
    protected Psr17Factory $factory;

    protected function setUp(): void
    {
        $this->http = new MockHttpClient();
        $this->factory = new Psr17Factory();
    }

    protected function makeClient(
        ?string $eventKey = 'test-event-key',
        ?string $signingKey = 'signkey-test-abc',
        ?string $env = null,
        ?string $devServerUrl = null,
    ): Client {
        $config = new Config(
            eventKey: $eventKey,
            signingKey: $signingKey,
            httpClient: $this->http,
            requestFactory: $this->factory,
            streamFactory: $this->factory,
            env: $env,
            devServerUrl: $devServerUrl,
        );
        return new Client($config);
    }

    /**
     * @param array<string, mixed>|list<mixed>|string $body
     */
    protected function queueResponse(int $status, array|string $body = '', array $headers = []): void
    {
        $payload = is_string($body) ? $body : json_encode($body, JSON_THROW_ON_ERROR);
        $stream = $this->factory->createStream((string) $payload);
        $response = new Response($status, array_merge(['Content-Type' => 'application/json'], $headers), $stream);
        $this->http->addResponse($response);
    }

    protected function lastRequest(): RequestInterface
    {
        $requests = $this->http->getRequests();
        return $requests[count($requests) - 1];
    }
}
