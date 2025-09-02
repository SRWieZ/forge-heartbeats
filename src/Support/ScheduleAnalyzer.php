<?php

namespace SRWieZ\ForgeHeartbeats\Support;

use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use SRWieZ\ForgeHeartbeats\DTOs\ScheduledTask;

class ScheduleAnalyzer
{
    public function __construct(
        private Schedule $schedule
    ) {
    }

    /**
     * Get all scheduled tasks that should be monitored.
     *
     * @return array<ScheduledTask>
     */
    public function getMonitorableTasks(): array
    {
        $tasks = [];

        foreach ($this->schedule->events() as $event) {
            if (! $this->isCommandEvent($event)) {
                continue; // Only monitor command events
            }

            $task = ScheduledTask::fromSchedulerEvent($event);
            
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
    public function getNamedTasks(): array
    {
        return array_filter(
            $this->getMonitorableTasks(),
            fn (ScheduledTask $task) => ! empty($task->name)
        );
    }

    /**
     * Get tasks that don't have names (can't be monitored).
     *
     * @return array<Event>
     */
    public function getUnnamedTasks(): array
    {
        $unnamedTasks = [];

        foreach ($this->schedule->events() as $event) {
            if (! $this->isCommandEvent($event)) {
                continue;
            }

            $task = ScheduledTask::fromSchedulerEvent($event);
            
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
    public function getDuplicateTasks(): array
    {
        $tasks = $this->getNamedTasks();
        $grouped = [];

        foreach ($tasks as $task) {
            $name = $task->getDisplayName();
            $grouped[$name][] = $task;
        }

        return array_filter($grouped, fn (array $group) => count($group) > 1);
    }

    private function isCommandEvent(Event $event): bool
    {
        return str_contains($event->command, 'artisan') || !empty($event->command);
    }
}