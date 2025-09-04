<?php

namespace SRWieZ\ForgeHeartbeats\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use SRWieZ\ForgeHeartbeats\Events\HeartbeatPinged;
use SRWieZ\ForgeHeartbeats\Http\Client\ForgeClientInterface;

class PingHeartbeatJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries;

    public int $retryAfter;

    public function __construct(
        private string $pingUrl,
        private string $taskName,
        private string $eventType = 'finished'
    ) {
        $this->tries = config('forge-heartbeats.queue.max_attempts', 3);
        $this->retryAfter = config('forge-heartbeats.queue.retry_after', 60);

        $this->onQueue(config('forge-heartbeats.queue.name', 'default'));
        $this->onConnection(config('forge-heartbeats.queue.connection', 'default'));
    }

    public function handle(ForgeClientInterface $forgeClient): void
    {
        $success = $forgeClient->pingHeartbeat($this->pingUrl);

        // Dispatch event for monitoring/logging purposes
        HeartbeatPinged::dispatch(
            $this->taskName,
            $this->pingUrl,
            $success,
            $this->eventType
        );
    }

    public function failed(\Throwable $exception): void
    {
        HeartbeatPinged::dispatch(
            $this->taskName,
            $this->pingUrl,
            false,
            $this->eventType,
            $exception->getMessage()
        );
    }
}
