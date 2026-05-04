<?php

declare(strict_types=1);

namespace Highfive\Inngest\Laravel;

use Highfive\Inngest\Cancellation\CancellationApi;
use Highfive\Inngest\Client;
use Highfive\Inngest\Event\EventApi;
use Highfive\Inngest\Runs\RunsApi;
use Illuminate\Support\Facades\Facade;

/**
 * @method static EventApi events()
 * @method static RunsApi runs()
 * @method static CancellationApi cancellations()
 *
 * @see Client
 */
final class InngestFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return Client::class;
    }
}
