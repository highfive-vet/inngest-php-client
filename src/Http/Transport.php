<?php

declare(strict_types=1);

namespace Highfive\Inngest\Http;

use Highfive\Inngest\Config;
use Highfive\Inngest\Exception\AuthenticationException;
use Highfive\Inngest\Exception\BadRequestException;
use Highfive\Inngest\Exception\InngestException;
use Highfive\Inngest\Exception\NotFoundException;
use Highfive\Inngest\Exception\RateLimitException;
use Highfive\Inngest\Exception\ServerException;
use Highfive\Inngest\Exception\TransportException;
use JsonException;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * @internal
 */
final class Transport
{
    public function __construct(private readonly Config $config)
    {
    }

    /**
     * @param array<string, string> $headers
     * @return array<string, mixed>
     */
    public function ingest(string $path, mixed $body, array $headers = []): array
    {
        $url = $this->config->eventBaseUrl . $path;

        return $this->send('POST', $url, $body, $this->mergeIngestHeaders($headers));
    }

    /**
     * @param array<string, string|int|null> $query
     * @return array<string, mixed>
     */
    public function apiGet(string $path, array $query = []): array
    {
        $url = $this->config->apiBaseUrl . $path;
        $filtered = array_filter($query, static fn ($v) => $v !== null);
        if ($filtered !== []) {
            $url .= '?' . http_build_query($filtered);
        }

        return $this->send('GET', $url, null, $this->mergeApiHeaders());
    }

    /**
     * @return array<string, mixed>
     */
    public function apiPost(string $path, mixed $body): array
    {
        $url = $this->config->apiBaseUrl . $path;

        return $this->send('POST', $url, $body, $this->mergeApiHeaders());
    }

    /**
     * Unwraps Inngest's `{ "data": ... }` envelope.
     *
     * @param array<string, mixed> $response
     * @return array<string, mixed>
     */
    public static function unwrap(array $response): array
    {
        $payload = $response['data'] ?? $response;
        return is_array($payload) ? $payload : [];
    }

    /**
     * @param array<string, string> $headers
     * @return array<string, mixed>
     */
    private function send(string $method, string $url, mixed $body, array $headers): array
    {
        $request = $this->config->requestFactory->createRequest($method, $url);

        foreach ($headers as $name => $value) {
            $request = $request->withHeader($name, $value);
        }

        if ($body !== null) {
            try {
                $json = json_encode($body, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
            } catch (JsonException $e) {
                throw new InngestException('Failed to encode request body: ' . $e->getMessage(), previous: $e);
            }
            $stream = $this->config->streamFactory->createStream($json);
            $request = $request->withBody($stream)->withHeader('Content-Type', 'application/json');
        }

        try {
            $response = $this->config->httpClient->sendRequest($request);
        } catch (ClientExceptionInterface $e) {
            throw new TransportException('HTTP transport error: ' . $e->getMessage(), previous: $e);
        }

        return $this->decode($response);
    }

    /**
     * @return array<string, mixed>
     */
    private function decode(ResponseInterface $response): array
    {
        $status = $response->getStatusCode();
        $raw = (string) $response->getBody();

        if ($status >= 200 && $status < 300) {
            if ($raw === '') {
                return [];
            }
            try {
                $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
            } catch (JsonException $e) {
                throw new InngestException('Failed to decode response: ' . $e->getMessage(), $status, $raw, $e);
            }
            return is_array($decoded) ? $decoded : ['data' => $decoded];
        }

        $decoded = $raw === '' ? null : json_decode($raw, true);
        $message = $this->extractMessage($decoded, $raw) ?? sprintf('Inngest API request failed with status %d', $status);

        throw match (true) {
            $status === 400 => new BadRequestException($message, $status, $raw),
            $status === 401 || $status === 403 => new AuthenticationException($message, $status, $raw),
            $status === 404 => new NotFoundException($message, $status, $raw),
            $status === 429 => new RateLimitException($message, $status, $raw),
            $status >= 500 => new ServerException($message, $status, $raw),
            default => new InngestException($message, $status, $raw),
        };
    }

    private function extractMessage(mixed $decoded, string $raw): ?string
    {
        if ($raw === '') {
            return null;
        }
        if (!is_array($decoded)) {
            return $raw;
        }
        foreach (['error', 'message', 'detail'] as $key) {
            if (isset($decoded[$key]) && is_string($decoded[$key])) {
                return $decoded[$key];
            }
        }
        return $raw;
    }

    /**
     * @param array<string, string> $extra
     * @return array<string, string>
     */
    private function mergeIngestHeaders(array $extra): array
    {
        $headers = ['Accept' => 'application/json'];
        if ($this->config->env !== null) {
            $headers['x-inngest-env'] = $this->config->env;
        }
        return array_merge($headers, $extra);
    }

    /**
     * @return array<string, string>
     */
    private function mergeApiHeaders(): array
    {
        if ($this->config->signingKey === null) {
            throw new InngestException('A signing key is required for Inngest REST API requests.');
        }
        $headers = [
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $this->config->signingKey,
        ];
        if ($this->config->env !== null) {
            $headers['x-inngest-env'] = $this->config->env;
        }
        return $headers;
    }
}
