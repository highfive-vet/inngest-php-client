<?php

declare(strict_types=1);

namespace Highfive\Inngest;

use Highfive\Inngest\Cancellation\CancellationApi;
use Highfive\Inngest\Event\EventApi;
use Highfive\Inngest\Http\Transport;
use Highfive\Inngest\Runs\RunsApi;
use Http\Discovery\Psr17FactoryDiscovery;
use Http\Discovery\Psr18ClientDiscovery;

final class Client
{
    private readonly Transport $transport;
    private readonly EventApi $events;
    private readonly RunsApi $runs;
    private readonly CancellationApi $cancellations;

    public function __construct(public readonly Config $config)
    {
        $this->transport = new Transport($config);
        $this->events = new EventApi($this->transport, $config->eventKey);
        $this->runs = new RunsApi($this->transport);
        $this->cancellations = new CancellationApi($this->transport);
    }

    public static function create(
        ?string $eventKey = null,
        ?string $signingKey = null,
        ?string $env = null,
        ?string $devServerUrl = null,
        ?string $eventBaseUrl = null,
        ?string $apiBaseUrl = null,
    ): self {
        $config = new Config(
            eventKey: $eventKey,
            signingKey: $signingKey,
            httpClient: Psr18ClientDiscovery::find(),
            requestFactory: Psr17FactoryDiscovery::findRequestFactory(),
            streamFactory: Psr17FactoryDiscovery::findStreamFactory(),
            env: $env,
            eventBaseUrl: $eventBaseUrl,
            apiBaseUrl: $apiBaseUrl,
            devServerUrl: $devServerUrl,
        );
        return new self($config);
    }

    public function events(): EventApi
    {
        return $this->events;
    }

    public function runs(): RunsApi
    {
        return $this->runs;
    }

    public function cancellations(): CancellationApi
    {
        return $this->cancellations;
    }
}
