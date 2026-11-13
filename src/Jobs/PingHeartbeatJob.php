<?php

namespace SRWieZ\ForgeHeartbeats\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use SRWieZ\ForgeHeartbeats\Events\HeartbeatPinged;

class PingHeartbeatJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(
        private string $pingUrl,
        private string $taskName,
        private string $eventType = 'finished'
    ) {
        $this->tries = config('forge-heartbeats.queue.max_attempts', 3);
        // Configs published before 2.0 only have queue.retry_after
        $this->backoff = (int) (config('forge-heartbeats.queue.backoff') ?? config('forge-heartbeats.queue.retry_after', 60));

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
        $success = Http::get($this->pingUrl)->successful();

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
