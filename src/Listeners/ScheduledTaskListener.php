<?php

namespace SRWieZ\ForgeHeartbeats\Listeners;

use Illuminate\Console\Events\ScheduledBackgroundTaskFinished;
use Illuminate\Console\Events\ScheduledTaskFailed;
use Illuminate\Console\Events\ScheduledTaskFinished;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Support\Facades\Log;
use SRWieZ\ForgeHeartbeats\DTOs\ScheduledTask;
use SRWieZ\ForgeHeartbeats\Jobs\PingHeartbeatJob;
use SRWieZ\ForgeHeartbeats\Support\HeartbeatManager;
use SRWieZ\ForgeHeartbeats\Support\TaskMetadataRegistry;

class ScheduledTaskListener
{
    public function __construct(
        private HeartbeatManager $heartbeatManager
    ) {}

    public function handle(
        ScheduledTaskFinished|ScheduledBackgroundTaskFinished|ScheduledTaskFailed $event
    ): void {
        // Determine the event type and set the appropriate status
        $eventType = match (true) {
            $event instanceof ScheduledTaskFailed => 'failed',
            $event instanceof ScheduledTaskFinished,
            $event instanceof ScheduledBackgroundTaskFinished => 'finished',
        };

        $this->pingHeartbeat($event->task, $eventType);
    }

    private function pingHeartbeat(Event $event, string $eventType): void
    {
        // Get metadata for the event
        $metadata = TaskMetadataRegistry::getTaskMetadata($event);

        // Create a ScheduledTask with full metadata
        $scheduledTask = ScheduledTask::fromSchedulerEvent($event, $metadata);

        // Check if monitoring is disabled for this task
        if ($scheduledTask->skipMonitoring) {
            Log::debug("Skipping monitoring for task: {$scheduledTask->name}");

            return;
        }

        // Find the corresponding heartbeat
        $heartbeat = $this->heartbeatManager->findHeartbeatForTask($scheduledTask);

        if (! $heartbeat) {
            Log::debug("No heartbeat found for task: {$scheduledTask->name}");

            return;
        }

        // Dispatch the ping job
        PingHeartbeatJob::dispatch(
            pingUrl: $heartbeat->pingUrl,
            taskName: $scheduledTask->getDisplayName(),
            eventType: $eventType
        );
    }
}
