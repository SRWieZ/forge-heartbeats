<?php

namespace SRWieZ\ForgeHeartbeats\Commands;

use Illuminate\Console\Command;
use SRWieZ\ForgeHeartbeats\Support\HeartbeatManager;
use SRWieZ\ForgeHeartbeats\Support\ScheduleAnalyzer;
use SRWieZ\ForgeHeartbeats\Support\TaskMatcher;

use function Termwind\render;

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
        /** @var view-string $viewName */
        $viewName = 'forge-heartbeats::list';
        render(view($viewName, [
            'matched' => $matchResult['matched'],
            'unmatchedTasks' => $matchResult['unmatched_tasks'],
            'orphanedHeartbeats' => $matchResult['orphaned_heartbeats'],
            'unnamedTasks' => $unnamedTasks,
        ]));
    }
}
