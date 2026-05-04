<?php

declare(strict_types=1);

namespace Highfive\Inngest\Cancellation;

use Highfive\Inngest\Http\Transport;

final class CancellationApi
{
    public function __construct(private readonly Transport $transport)
    {
    }

    public function bulk(CancellationRequest $request): Cancellation
    {
        $response = $this->transport->apiPost('/v1/cancellations', $request->toArray());

        return Cancellation::fromArray(Transport::unwrap($response));
    }
}
