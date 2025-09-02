<?php

namespace SRWieZ\ForgeHeartbeats\Listeners;

use Illuminate\Console\Events\ScheduledTaskFailed;
use Illuminate\Console\Events\ScheduledTaskFinished;
use Illuminate\Console\Events\ScheduledTaskSkipped;
use Illuminate\Console\Events\ScheduledTaskStarting;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\Log;
use SRWieZ\ForgeHeartbeats\DTOs\ScheduledTask;
use SRWieZ\ForgeHeartbeats\Jobs\PingHeartbeatJob;
use SRWieZ\ForgeHeartbeats\Support\HeartbeatManager;

class ScheduledTaskSubscriber
{
    public function __construct(
        private HeartbeatManager $heartbeatManager
    ) {
    }

    public function handleTaskStarting(ScheduledTaskStarting $event): void
    {
        // We could ping on start, but typically we only ping on finish/failure
        // This is configurable behavior that could be added later
        Log::debug("Scheduled task starting: {$event->task->command}");
    }

    public function handleTaskFinished(ScheduledTaskFinished $event): void
    {
        $this->pingHeartbeat($event->task->command, 'finished');
    }

    public function handleTaskFailed(ScheduledTaskFailed $event): void
    {
        $this->pingHeartbeat($event->task->command, 'failed');
    }

    public function handleTaskSkipped(ScheduledTaskSkipped $event): void
    {
        // Typically we don't ping for skipped tasks, but this could be configurable
        Log::debug("Scheduled task skipped: {$event->task->command}");
    }

    public function subscribe(Dispatcher $events): array
    {
        return [
            ScheduledTaskStarting::class => 'handleTaskStarting',
            ScheduledTaskFinished::class => 'handleTaskFinished', 
            ScheduledTaskFailed::class => 'handleTaskFailed',
            ScheduledTaskSkipped::class => 'handleTaskSkipped',
        ];
    }

    private function pingHeartbeat(string $command, string $eventType): void
    {
        try {
            // Create a ScheduledTask from the command to extract the name
            $scheduledTask = new ScheduledTask(
                name: $this->extractCommandName($command),
                cronExpression: '', // Not needed for ping
                timezone: null
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
        } catch (\Throwable $e) {
            Log::error("Error setting up heartbeat ping for command {$command}: " . $e->getMessage());
        }
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