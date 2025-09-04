<?php

namespace SRWieZ\ForgeHeartbeats\Listeners;

use Illuminate\Console\Events\ScheduledBackgroundTaskFinished;
use Illuminate\Console\Events\ScheduledTaskFailed;
use Illuminate\Console\Events\ScheduledTaskFinished;
use Illuminate\Support\Facades\Log;
use SRWieZ\ForgeHeartbeats\DTOs\ScheduledTask;
use SRWieZ\ForgeHeartbeats\Jobs\PingHeartbeatJob;
use SRWieZ\ForgeHeartbeats\Support\HeartbeatManager;

class ScheduledTaskListener
{
    public function __construct(
        private HeartbeatManager $heartbeatManager
    ) {}

    public function handleBackgroundTaskFinished(ScheduledBackgroundTaskFinished $event): void
    {
        $this->pingHeartbeat($event->task->command, 'finished');
    }

    public function handleTaskFinished(ScheduledTaskFinished $event): void
    {
        $this->pingHeartbeat($event->task->command, 'finished');
    }

    public function handleTaskFailed(ScheduledTaskFailed $event): void
    {
        $this->pingHeartbeat($event->task->command, 'failed');
    }

    private function pingHeartbeat(string $command, string $eventType): void
    {
        // Create a ScheduledTask from the command to extract the name
        $scheduledTask = new ScheduledTask(
            name: $this->extractCommandName($command),
            cronExpression: '', // Not needed for ping
        );

        // Find the corresponding heartbeat
        $heartbeat = $this->heartbeatManager->findHeartbeatForTask($scheduledTask);

        if (! $heartbeat) {
            Log::debug("No heartbeat found for task: {$scheduledTask->name}");

            return;
        }

        // Dispatch the ping job
        PingHeartbeatJob::dispatch(
            pingUrl: $heartbeat->pingUrl,
            taskName: $scheduledTask->name,
            eventType: $eventType
        );
    }

    private function extractCommandName(string $command): string
    {
        // Remove php artisan prefix
        $command = preg_replace('/^.*php\s+artisan\s+/', '', $command);

        // Extract just the command name (first word)
        $parts = explode(' ', trim($command));

        return $parts[0] ?? $command;
    }
}
