<?php

namespace SRWieZ\ForgeHeartbeats\Commands;

use Illuminate\Console\Command;
use SRWieZ\ForgeHeartbeats\Enums\HeartbeatStatus;
use SRWieZ\ForgeHeartbeats\Http\Client\FrequencyEnum;
use SRWieZ\ForgeHeartbeats\Support\HeartbeatManager;
use SRWieZ\ForgeHeartbeats\Support\ScheduleAnalyzer;
use SRWieZ\ForgeHeartbeats\Support\TaskMatcher;

class ListCommand extends Command
{
    protected $signature = 'forge-heartbeats:list';

    protected $description = 'Display all Forge heartbeats and their status';

    public function handle(
        HeartbeatManager $heartbeatManager,
        ScheduleAnalyzer $scheduleAnalyzer,
        TaskMatcher $taskMatcher
    ): int {
        $this->info('🔍 Fetching heartbeats from Forge...');

        $heartbeats = $heartbeatManager->getHeartbeats(true);
        $tasks = $scheduleAnalyzer->getNamedTasks($heartbeatManager);
        $unnamedTasks = $scheduleAnalyzer->getUnnamedTasks($heartbeatManager);

        if (empty($heartbeats) && empty($tasks)) {
            $this->warn('⚠️  No heartbeats found and no scheduled tasks to monitor.');

            return self::SUCCESS;
        }

        $matchResult = $taskMatcher->match($tasks, $heartbeats);

        $this->displayHeartbeats($matchResult, $unnamedTasks);

        return self::SUCCESS;
    }

    private function displayHeartbeats(array $matchResult, array $unnamedTasks): void
    {
        // Display monitored tasks
        if (! empty($matchResult['matched'])) {
            $this->newLine();
            $this->line('<fg=blue;options=bold>📋 Monitored Tasks</>');

            $rows = [];
            foreach ($matchResult['matched'] as $match) {
                $status = HeartbeatStatus::tryFrom($match['heartbeat']->status);
                $statusDisplay = $status?->displayText() ?? '❓ ' . ucfirst($match['heartbeat']->status);

                // Check if task and heartbeat are synced
                $isSynced = $this->isTaskSynced($match['task'], $match['heartbeat']);
                $syncStatus = $isSynced ? '✅ Synced' : '⚠️  Out of Sync';

                $rows[] = [
                    $match['task']->getDisplayName(),
                    $statusDisplay,
                    $match['task']->cronExpression,
                    $match['heartbeat']->gracePeriod . 'min',
                    $syncStatus,
                ];
            }

            $this->table(['Task', 'Status', 'Schedule', 'Grace Period', 'Synced'], $rows);
        }

        // Display unmonitored tasks
        if (! empty($matchResult['unmatched_tasks'])) {
            $this->newLine();
            $this->line('<fg=yellow;options=bold>⚠️  Unmonitored Tasks</>');
            $this->line('<fg=gray>These tasks exist in your schedule but have no corresponding heartbeat</>');

            $rows = [];
            foreach ($matchResult['unmatched_tasks'] as $task) {
                $rows[] = [$task->getDisplayName(), $task->cronExpression, '❌ Not Synced'];
            }

            $this->table(['Task', 'Schedule', 'Synced'], $rows);
        }

        // Display orphaned heartbeats
        if (! empty($matchResult['orphaned_heartbeats'])) {
            $this->newLine();
            $this->line('<fg=red;options=bold>🗑️  Orphaned Heartbeats</>');
            $this->line('<fg=gray>These heartbeats exist in Forge but have no corresponding scheduled task</>');

            $rows = [];
            foreach ($matchResult['orphaned_heartbeats'] as $heartbeat) {
                $status = HeartbeatStatus::tryFrom($heartbeat->status);
                $statusDisplay = $status?->displayText() ?? '❓ ' . ucfirst($heartbeat->status);

                $rows[] = [$heartbeat->name, $statusDisplay, '🗑️  Orphaned'];
            }

            $this->table(['Heartbeat', 'Status', 'Synced'], $rows);
        }

        // Display unnamed tasks
        if (! empty($unnamedTasks)) {
            $this->newLine();
            $this->line('<fg=yellow;options=bold>❓ Unnamed Tasks</>');
            $this->line('<fg=gray>These tasks cannot be monitored because they don\'t have identifiable names</>');
            $this->line('<fg=gray>' . count($unnamedTasks) . ' unnamed task(s) found</>');
        }

        // Display message if nothing found
        if (empty($matchResult['matched']) && empty($matchResult['unmatched_tasks']) && empty($matchResult['orphaned_heartbeats']) && empty($unnamedTasks)) {
            $this->line('<fg=yellow>⚠️  No scheduled tasks or heartbeats found</>');
        }

        // Footer message
        $this->newLine();
        $this->line('<fg=gray>Run </><fg=blue>php artisan forge-heartbeats:sync</><fg=gray> to synchronize your schedule with Forge</>');
    }

    private function isTaskSynced($task, $heartbeat): bool
    {
        // Check grace period
        $expectedGracePeriod = $task->graceTimeInMinutes ?? config('forge-heartbeats.defaults.grace_period', 5);
        if ($heartbeat->gracePeriod !== $expectedGracePeriod) {
            return false;
        }

        // Check name
        if ($heartbeat->name !== $task->getDisplayName()) {
            return false;
        }

        // Check frequency configuration
        $frequency = FrequencyEnum::fromCronExpression($task->cronExpression);

        if ($frequency === FrequencyEnum::CUSTOM) {
            // Custom frequency - check if frequency is -1 and custom_frequency matches
            return $heartbeat->frequency === $frequency->value && $heartbeat->customFrequency === $task->cronExpression;
        } else {
            // Standard frequency - check if frequency matches and no custom frequency
            return $heartbeat->frequency === $frequency->value && $heartbeat->customFrequency === null;
        }
    }
}
