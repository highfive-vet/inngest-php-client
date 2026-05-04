<?php

declare(strict_types=1);

namespace Highfive\Inngest\Event;

use Highfive\Inngest\Exception\InngestException;
use Highfive\Inngest\Http\Transport;

final class EventApi
{
    public function __construct(
        private readonly Transport $transport,
        private readonly ?string $eventKey,
    ) {
    }

    public function send(DispatchableEvent $event): SendResult
    {
        return $this->doSend($this->normalize($event)->toArray());
    }

    /**
     * @param iterable<DispatchableEvent> $events
     */
    public function sendMany(iterable $events): SendResult
    {
        $payload = [];
        foreach ($events as $event) {
            $payload[] = $this->normalize($event)->toArray();
        }
        if ($payload === []) {
            return new SendResult([], 200);
        }
        return $this->doSend($payload);
    }

    private function doSend(mixed $body): SendResult
    {
        if ($this->eventKey === null || $this->eventKey === '') {
            throw new InngestException('An event key is required to send events to Inngest.');
        }

        $response = $this->transport->ingest('/e/' . rawurlencode($this->eventKey), $body);

        return SendResult::fromArray($response);
    }

    private function normalize(DispatchableEvent $event): Event
    {
        return $event->toInngestEvent();
    }
}
