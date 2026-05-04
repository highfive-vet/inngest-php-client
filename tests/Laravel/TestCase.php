<?php

declare(strict_types=1);

namespace Highfive\Inngest\Tests\Laravel;

use Highfive\Inngest\Laravel\InngestFacade;
use Highfive\Inngest\Laravel\InngestServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app): array
    {
        return [InngestServiceProvider::class];
    }

    protected function getPackageAliases($app): array
    {
        return ['Inngest' => InngestFacade::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('inngest.event_key', 'env-event-key');
        $app['config']->set('inngest.signing_key', 'env-signing-key');
        $app['config']->set('inngest.env', 'staging');
    }
}
