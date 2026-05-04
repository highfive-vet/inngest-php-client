<?php

declare(strict_types=1);

use Highfive\Inngest\Config as InngestConfig;

return [
    /*
    |--------------------------------------------------------------------------
    | Inngest credentials
    |--------------------------------------------------------------------------
    |
    | The event key authenticates outbound calls to https://inn.gs (sending
    | events). The signing key authenticates v1 REST API calls (runs,
    | cancellations). Both are issued in the Inngest dashboard.
    |
    */
    'event_key' => env('INNGEST_EVENT_KEY', null),
    'signing_key' => env('INNGEST_SIGNING_KEY', null),

    /*
    |--------------------------------------------------------------------------
    | Branch environment
    |--------------------------------------------------------------------------
    |
    | When set, every request includes an "x-inngest-env" header so events
    | and run lookups target a specific Inngest branch environment.
    |
    */
    'env' => env('INNGEST_ENV', null),

    /*
    |--------------------------------------------------------------------------
    | Dev server
    |--------------------------------------------------------------------------
    |
    | If set, both the event ingestion URL and the REST API URL are routed
    | to the local Inngest dev server (https://www.inngest.com/docs/local-development).
    |
    */
    'dev_server_url' => env('INNGEST_DEV_SERVER_URL', null),

    /*
    |--------------------------------------------------------------------------
    | Base URL overrides
    |--------------------------------------------------------------------------
    |
    | Rarely needed. Use to point at a self-hosted Inngest deployment.
    |
    */
    'event_base_url' => env('INNGEST_EVENT_BASE_URL', InngestConfig::DEFAULT_EVENT_BASE_URL),
    'api_base_url' => env('INNGEST_API_BASE_URL', InngestConfig::DEFAULT_API_BASE_URL),
];
