<?php

declare(strict_types=1);

namespace Highfive\Inngest\Laravel;

use Highfive\Inngest\Cancellation\CancellationApi;
use Highfive\Inngest\Client;
use Highfive\Inngest\Event\EventApi;
use Highfive\Inngest\Runs\RunsApi;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

final class InngestServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom($this->configPath(), 'inngest');

        $this->app->singleton(Client::class, function ($app): Client {
            $config = $app['config']->get('inngest', []);

            return Client::create(
                eventKey: $config['event_key'] ?? null,
                signingKey: $config['signing_key'] ?? null,
                env: $config['env'] ?? null,
                devServerUrl: $config['dev_server_url'] ?? null,
                eventBaseUrl: $config['event_base_url'] ?? null,
                apiBaseUrl: $config['api_base_url'] ?? null,
            );
        });

        $this->app->alias(Client::class, 'inngest');

        $this->app->singleton(EventApi::class, fn ($app) => $app->make(Client::class)->events());
        $this->app->singleton(RunsApi::class, fn ($app) => $app->make(Client::class)->runs());
        $this->app->singleton(CancellationApi::class, fn ($app) => $app->make(Client::class)->cancellations());
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                $this->configPath() => $this->app->configPath('inngest.php'),
            ], 'inngest-config');
        }
    }

    /**
     * @return list<string>
     */
    public function provides(): array
    {
        return [
            Client::class,
            EventApi::class,
            RunsApi::class,
            CancellationApi::class,
            'inngest',
        ];
    }

    private function configPath(): string
    {
        return __DIR__ . '/../../config/inngest.php';
    }
}
