<?php

namespace SRWieZ\ForgeHeartbeats\Support;

use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Cache;
use SRWieZ\ForgeHeartbeats\Contracts\ForgeClientInterface;
use SRWieZ\ForgeHeartbeats\DTOs\Heartbeat;
use SRWieZ\ForgeHeartbeats\DTOs\ScheduledTask;
use SRWieZ\ForgeHeartbeats\Enums\FrequencyEnum;

class HeartbeatManager
{
    private array $taskMetadata = [];

    public function __construct(
        private ForgeClientInterface $forgeClient,
        private ScheduleAnalyzer $scheduleAnalyzer,
        private TaskMatcher $taskMatcher
    ) {}

    public function setHeartbeatName(Event $event, string $name): void
    {
        $taskId = spl_object_hash($event);
        $this->taskMetadata[$taskId]['heartbeat_name'] = $name;
    }

    public function setGraceTime(Event $event, int $minutes): void
    {
        $taskId = spl_object_hash($event);
        $this->taskMetadata[$taskId]['grace_time'] = $minutes;
    }

    public function setSkipMonitoring(Event $event, bool $skip): void
    {
        $taskId = spl_object_hash($event);
        $this->taskMetadata[$taskId]['skip_monitoring'] = $skip;
    }

    public function getTaskMetadata(Event $event): array
    {
        $taskId = spl_object_hash($event);

        return $this->taskMetadata[$taskId] ?? [];
    }

    /**
     * Get all heartbeats from Forge (with caching).
     *
     * @return array<Heartbeat>
     */
    public function getHeartbeats(): array
    {
        $cacheKey = config('forge-heartbeats.cache.prefix') . ':heartbeats';
        $cacheTtl = config('forge-heartbeats.cache.ttl', 300);

        return Cache::remember($cacheKey, $cacheTtl, function () {
            return $this->forgeClient->listHeartbeats();
        });
    }

    /**
     * Sync the current schedule with Forge heartbeats.
     */
    public function syncHeartbeats(bool $keepOldHeartbeats = false): array
    {
        $tasks = $this->scheduleAnalyzer->getNamedTasks($this);
        $heartbeats = $this->getHeartbeats();

        $matchResult = $this->taskMatcher->match($tasks, $heartbeats);

        $created = [];
        $updated = [];
        $deleted = [];

        // Create heartbeats for unmatched tasks
        foreach ($matchResult['unmatched_tasks'] as $task) {
            $heartbeat = $this->createHeartbeatForTask($task);
            $created[] = $heartbeat;
        }

        // Update matched heartbeats if needed
        foreach ($matchResult['matched'] as $match) {
            $task = $match['task'];
            $heartbeat = $match['heartbeat'];

            if ($this->shouldUpdateHeartbeat($task, $heartbeat)) {
                $updatedHeartbeat = $this->updateHeartbeatForTask($task, $heartbeat);
                $updated[] = $updatedHeartbeat;
            }
        }

        // Delete orphaned heartbeats (unless keeping old ones)
        if (! $keepOldHeartbeats) {
            foreach ($matchResult['orphaned_heartbeats'] as $heartbeat) {
                $this->forgeClient->deleteHeartbeat($heartbeat->id);
                $deleted[] = $heartbeat;
            }
        }

        // Clear cache after sync
        $this->clearHeartbeatsCache();

        return [
            'created' => $created,
            'updated' => $updated,
            'deleted' => $deleted,
        ];
    }

    /**
     * Find a heartbeat for a given task by name.
     */
    public function findHeartbeatForTask(ScheduledTask $task): ?Heartbeat
    {
        $heartbeats = $this->getHeartbeats();

        return $this->taskMatcher->findHeartbeatForTask($task, $heartbeats);
    }

    private function createHeartbeatForTask(ScheduledTask $task): Heartbeat
    {
        $gracePeriod = $task->graceTimeInMinutes ?? config('forge-heartbeats.defaults.grace_period', 5);

        $frequency = FrequencyEnum::fromCronExpression($task->cronExpression);

        return $this->forgeClient->createHeartbeat(
            name: $task->getDisplayName(),
            gracePeriod: $gracePeriod,
            frequency: $frequency->value,
            customFrequency: $frequency === FrequencyEnum::CUSTOM ? $task->cronExpression : null
        );
    }

    private function updateHeartbeatForTask(ScheduledTask $task, Heartbeat $heartbeat): Heartbeat
    {
        $gracePeriod = $task->graceTimeInMinutes ?? config('forge-heartbeats.defaults.grace_period', 5);

        $frequency = FrequencyEnum::fromCronExpression($task->cronExpression);

        return $this->forgeClient->updateHeartbeat(
            heartbeatId: $heartbeat->id,
            name: $task->getDisplayName(),
            gracePeriod: $gracePeriod,
            frequency: $frequency->value,
            customFrequency: $frequency === FrequencyEnum::CUSTOM ? $task->cronExpression : null
        );
    }

    private function shouldUpdateHeartbeat(ScheduledTask $task, Heartbeat $heartbeat): bool
    {
        $expectedGracePeriod = $task->graceTimeInMinutes ?? config('forge-heartbeats.defaults.grace_period', 5);
        $expectedName = $task->getDisplayName();

        if ($heartbeat->name !== $expectedName || $heartbeat->gracePeriod !== $expectedGracePeriod) {
            return true;
        }

        // Check frequency configuration
        $frequency = FrequencyEnum::fromCronExpression($task->cronExpression);

        if ($frequency === FrequencyEnum::CUSTOM) {
            // Custom frequency - check if frequency is -1 and custom_frequency matches
            return $heartbeat->frequency !== $frequency->value || $heartbeat->customFrequency !== $task->cronExpression;
        } else {
            // Standard frequency - check if frequency matches and custom_frequency is null
            return $heartbeat->frequency !== $frequency->value || $heartbeat->customFrequency !== null;
        }
    }

    private function clearHeartbeatsCache(): void
    {
        $cacheKey = config('forge-heartbeats.cache.prefix') . ':heartbeats';
        Cache::forget($cacheKey);
    }
}
