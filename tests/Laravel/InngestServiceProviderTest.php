<?php

declare(strict_types=1);

use Highfive\Inngest\Cancellation\CancellationApi;
use Highfive\Inngest\Client;
use Highfive\Inngest\Config;
use Highfive\Inngest\Event\EventApi;
use Highfive\Inngest\Laravel\InngestFacade;
use Highfive\Inngest\Runs\RunsApi;
use Highfive\Inngest\Tests\Laravel\TestCase;

uses(TestCase::class);

it('resolves the client from the container', function (): void {
    $client = $this->app->make(Client::class);

    expect($client)->toBeInstanceOf(Client::class);
    expect($client->config->eventKey)->toBe('env-event-key');
    expect($client->config->signingKey)->toBe('env-signing-key');
    expect($client->config->env)->toBe('staging');
    expect($client->config->eventBaseUrl)->toBe(Config::DEFAULT_EVENT_BASE_URL);
    expect($client->config->apiBaseUrl)->toBe(Config::DEFAULT_API_BASE_URL);
});

it('registers the sub apis and alias', function (): void {
    expect($this->app->make('inngest'))->toBe($this->app->make(Client::class));
    expect($this->app->make(EventApi::class))->toBeInstanceOf(EventApi::class);
    expect($this->app->make(RunsApi::class))->toBeInstanceOf(RunsApi::class);
    expect($this->app->make(CancellationApi::class))->toBeInstanceOf(CancellationApi::class);
});

it('resolves the facade to the client apis', function (): void {
    expect(InngestFacade::events())->toBeInstanceOf(EventApi::class);
    expect(InngestFacade::runs())->toBeInstanceOf(RunsApi::class);
});

it('flows the dev server override through config', function (): void {
    $this->app['config']->set('inngest.dev_server_url', 'http://localhost:8288');
    $this->app->forgetInstance(Client::class);

    $client = $this->app->make(Client::class);

    expect($client->config->eventBaseUrl)->toBe('http://localhost:8288');
    expect($client->config->apiBaseUrl)->toBe('http://localhost:8288');
});
