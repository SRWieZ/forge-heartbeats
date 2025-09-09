<?php

namespace SRWieZ\ForgeHeartbeats\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use SRWieZ\ForgeHeartbeats\Events\HeartbeatPinged;
use SRWieZ\ForgeHeartbeats\Http\Integrations\Forge\Requests\Heartbeats\PingHeartbeatRequest;

class PingHeartbeatJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $retryAfter = 60;

    public function __construct(
        private string $pingUrl,
        private string $taskName,
        private string $eventType = 'finished'
    ) {
        $this->tries = config('forge-heartbeats.queue.max_attempts', 3);
        $this->retryAfter = config('forge-heartbeats.queue.retry_after', 60);

        // Use Laravel's default queue connection if not specified
        if ($queueName = config('forge-heartbeats.queue.name')) {
            $this->onQueue($queueName);
        }

        if ($connection = config('forge-heartbeats.queue.connection')) {
            $this->onConnection($connection);
        }
    }

    public function handle(): void
    {
        $request = new PingHeartbeatRequest($this->pingUrl);
        $response = $request->send();

        $success = $response->successful();

        if (! $success) {
            // If ping failed, throw an exception to trigger the failed() method and retries
            throw new \Exception("Failed to ping heartbeat URL: {$this->pingUrl}");
        }

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
