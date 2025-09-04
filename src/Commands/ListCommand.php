<?php

namespace SRWieZ\ForgeHeartbeats\Commands;

use Illuminate\Console\Command;
use SRWieZ\ForgeHeartbeats\Exceptions\ForgeApiException;
use SRWieZ\ForgeHeartbeats\Exceptions\InvalidConfigException;
use SRWieZ\ForgeHeartbeats\Support\HeartbeatManager;
use SRWieZ\ForgeHeartbeats\Support\ScheduleAnalyzer;
use SRWieZ\ForgeHeartbeats\Support\TaskMatcher;

use function Termwind\render;

class ListCommand extends Command
{
    protected $signature = 'forge:heartbeats:list';

    protected $description = 'Display all Forge heartbeats and their status';

    public function handle(
        HeartbeatManager $heartbeatManager,
        ScheduleAnalyzer $scheduleAnalyzer,
        TaskMatcher $taskMatcher
    ): int {
        try {
            $this->info('🔍 Fetching heartbeats from Forge...');

            $heartbeats = $heartbeatManager->getHeartbeats();
            $tasks = $scheduleAnalyzer->getNamedTasks();
            $unnamedTasks = $scheduleAnalyzer->getUnnamedTasks();

            if (empty($heartbeats) && empty($tasks)) {
                $this->warn('⚠️  No heartbeats found and no scheduled tasks to monitor.');

                return self::SUCCESS;
            }

            $matchResult = $taskMatcher->match($tasks, $heartbeats);

            $this->displayHeartbeats($matchResult, $unnamedTasks);

            return self::SUCCESS;
        } catch (InvalidConfigException $e) {
            $this->error('❌ Configuration Error: ' . $e->getMessage());

            return self::FAILURE;
        } catch (ForgeApiException $e) {
            $this->error('❌ Forge API Error: ' . $e->getMessage());

            return self::FAILURE;
        } catch (\Throwable $e) {
            $this->error('❌ Unexpected Error: ' . $e->getMessage());

            return self::FAILURE;
        }
    }

    private function displayHeartbeats(array $matchResult, array $unnamedTasks): void
    {
        render(view('forge-heartbeats::list', [
            'matched' => $matchResult['matched'],
            'unmatchedTasks' => $matchResult['unmatched_tasks'],
            'orphanedHeartbeats' => $matchResult['orphaned_heartbeats'],
            'unnamedTasks' => $unnamedTasks,
        ]));
    }
}
