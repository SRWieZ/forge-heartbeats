<?php

namespace SRWieZ\ForgeHeartbeats\Support;

use SRWieZ\ForgeHeartbeats\DTOs\Heartbeat;
use SRWieZ\ForgeHeartbeats\DTOs\ScheduledTask;

class TaskMatcher
{
    /**
     * Match scheduled tasks to existing heartbeats by name.
     *
     * @param array<ScheduledTask> $tasks
     * @param array<Heartbeat> $heartbeats
     * @return array{matched: array, unmatched_tasks: array<ScheduledTask>, orphaned_heartbeats: array<Heartbeat>}
     */
    public function match(array $tasks, array $heartbeats): array
    {
        $matched = [];
        $unmatchedTasks = [];
        $heartbeatsByName = $this->groupHeartbeatsByName($heartbeats);
        $tasksByName = $this->groupTasksByName($tasks);

        // Find matches and unmatched tasks
        foreach ($tasksByName as $name => $tasksGroup) {
            if (isset($heartbeatsByName[$name])) {
                $matched[$name] = [
                    'task' => $tasksGroup[0], // Take the first task if there are duplicates
                    'heartbeat' => $heartbeatsByName[$name][0], // Take the first heartbeat if there are duplicates
                ];
                unset($heartbeatsByName[$name]);
            } else {
                $unmatchedTasks = array_merge($unmatchedTasks, $tasksGroup);
            }
        }

        // Remaining heartbeats are orphaned (no matching tasks)
        $orphanedHeartbeats = array_merge(...array_values($heartbeatsByName));

        return [
            'matched' => $matched,
            'unmatched_tasks' => $unmatchedTasks,
            'orphaned_heartbeats' => $orphanedHeartbeats,
        ];
    }

    /**
     * Find a heartbeat by task name.
     *
     * @param array<Heartbeat> $heartbeats
     */
    public function findHeartbeatForTask(ScheduledTask $task, array $heartbeats): ?Heartbeat
    {
        $taskName = $task->getDisplayName();

        foreach ($heartbeats as $heartbeat) {
            if ($heartbeat->name === $taskName) {
                return $heartbeat;
            }
        }

        return null;
    }

    /**
     * @param array<Heartbeat> $heartbeats
     * @return array<string, array<Heartbeat>>
     */
    private function groupHeartbeatsByName(array $heartbeats): array
    {
        $grouped = [];

        foreach ($heartbeats as $heartbeat) {
            $grouped[$heartbeat->name][] = $heartbeat;
        }

        return $grouped;
    }

    /**
     * @param array<ScheduledTask> $tasks
     * @return array<string, array<ScheduledTask>>
     */
    private function groupTasksByName(array $tasks): array
    {
        $grouped = [];

        foreach ($tasks as $task) {
            $name = $task->getDisplayName();
            $grouped[$name][] = $task;
        }

        return $grouped;
    }
}