<?php

namespace SRWieZ\ForgeHeartbeats\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class HeartbeatPinged
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly string $taskName,
        public readonly string $pingUrl,
        public readonly bool $success,
        public readonly string $eventType,
        public readonly ?string $error = null
    ) {}
}
