<?php

namespace SRWieZ\ForgeHeartbeats\Support;

use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use SRWieZ\ForgeHeartbeats\DTOs\ScheduledTask;

class ScheduleAnalyzer
{
    public function __construct(
        private Schedule $schedule
    ) {}

    /**
     * Get all scheduled tasks that should be monitored.
     *
     * @return array<ScheduledTask>
     */
    public function getMonitorableTasks(?HeartbeatManager $heartbeatManager = null): array
    {
        $tasks = [];

        foreach ($this->schedule->events() as $event) {
            if (! $this->isCommandEvent($event)) {
                continue; // Only monitor command events
            }

            $metadata = $heartbeatManager?->getTaskMetadata($event) ?? [];
            $task = ScheduledTask::fromSchedulerEvent($event, $metadata);

            if ($task->shouldBeMonitored()) {
                $tasks[] = $task;
            }
        }

        return $tasks;
    }

    /**
     * Get tasks that have names (can be monitored).
     *
     * @return array<ScheduledTask>
     */
    public function getNamedTasks(?HeartbeatManager $heartbeatManager = null): array
    {
        return array_filter(
            $this->getMonitorableTasks($heartbeatManager),
            fn (ScheduledTask $task) => ! empty($task->name)
        );
    }

    /**
     * Get tasks that don't have names (can't be monitored).
     *
     * @return array<Event>
     */
    public function getUnnamedTasks(?HeartbeatManager $heartbeatManager = null): array
    {
        $unnamedTasks = [];

        foreach ($this->schedule->events() as $event) {
            if (! $this->isCommandEvent($event)) {
                continue;
            }

            $metadata = $heartbeatManager?->getTaskMetadata($event) ?? [];
            $task = ScheduledTask::fromSchedulerEvent($event, $metadata);

            if (empty($task->name) && $task->shouldBeMonitored()) {
                $unnamedTasks[] = $event;
            }
        }

        return $unnamedTasks;
    }

    /**
     * Find duplicate task names.
     *
     * @return array<string, array<ScheduledTask>>
     */
    public function getDuplicateTasks(?HeartbeatManager $heartbeatManager = null): array
    {
        $tasks = $this->getNamedTasks($heartbeatManager);
        $grouped = [];

        foreach ($tasks as $task) {
            $name = $task->getDisplayName();
            $grouped[$name][] = $task;
        }

        return array_filter($grouped, fn (array $group) => count($group) > 1);
    }

    private function isCommandEvent(Event $event): bool
    {
        return str_contains($event->command, 'artisan') || ! empty($event->command);
    }
}
