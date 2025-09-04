<?php

namespace SRWieZ\ForgeHeartbeats\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use SRWieZ\ForgeHeartbeats\Contracts\ForgeClientInterface;
use SRWieZ\ForgeHeartbeats\Events\HeartbeatPinged;

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
        try {
            $success = $forgeClient->pingHeartbeat($this->pingUrl);

            if ($success) {
                Log::debug("Successfully pinged heartbeat for task: {$this->taskName}");
            } else {
                Log::warning("Failed to ping heartbeat for task: {$this->taskName}");
            }

            // Dispatch event for monitoring/logging purposes
            HeartbeatPinged::dispatch(
                $this->taskName,
                $this->pingUrl,
                $success,
                $this->eventType
            );
        } catch (\Throwable $e) {
            Log::error("Error pinging heartbeat for task {$this->taskName}: " . $e->getMessage());

            // Re-throw to trigger retry mechanism
            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("Failed to ping heartbeat for task {$this->taskName} after {$this->tries} attempts: " . $exception->getMessage());

        HeartbeatPinged::dispatch(
            $this->taskName,
            $this->pingUrl,
            false,
            $this->eventType,
            $exception->getMessage()
        );
    }
}
