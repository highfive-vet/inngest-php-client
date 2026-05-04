<?php

declare(strict_types=1);

namespace Highfive\Inngest\Event;

interface DispatchableEvent
{
    public function toInngestEvent(): Event;
}
