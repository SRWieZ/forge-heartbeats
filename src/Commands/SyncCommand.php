<?php

namespace SRWieZ\ForgeHeartbeats\Commands;

use Illuminate\Console\Command;
use SRWieZ\ForgeHeartbeats\Exceptions\ForgeApiException;
use SRWieZ\ForgeHeartbeats\Exceptions\InvalidConfigException;
use SRWieZ\ForgeHeartbeats\Support\HeartbeatManager;
use SRWieZ\ForgeHeartbeats\Support\ScheduleAnalyzer;

class SyncCommand extends Command
{
    protected $signature = 'forge-heartbeats:sync 
                           {--keep-old : Keep heartbeats that no longer have matching scheduled tasks}';

    protected $description = 'Sync Laravel scheduled tasks with Forge heartbeats';

    public function handle(
        HeartbeatManager $heartbeatManager,
        ScheduleAnalyzer $scheduleAnalyzer
    ): int {
        try {
            $this->info('🔍 Analyzing scheduled tasks...');

            $tasks = $scheduleAnalyzer->getNamedTasks();
            $unnamedTasks = $scheduleAnalyzer->getUnnamedTasks();
            $duplicateTasks = $scheduleAnalyzer->getDuplicateTasks();

            if (empty($tasks)) {
                $this->warn('⚠️  No scheduled tasks found to monitor.');

                return self::SUCCESS;
            }

            $this->info("📋 Found {count($tasks)} scheduled task(s) to monitor");

            if (! empty($unnamedTasks)) {
                $this->warn('⚠️  Found ' . count($unnamedTasks) . ' unnamed task(s) that cannot be monitored');
            }

            if (! empty($duplicateTasks)) {
                $this->warn('⚠️  Found duplicate task names: ' . implode(', ', array_keys($duplicateTasks)));
            }

            $keepOld = $this->option('keep-old');

            $this->info('🔄 Syncing with Forge...');

            $result = $heartbeatManager->syncHeartbeats($keepOld);

            $this->displayResults($result);

            $this->info('✅ Sync completed successfully');

            return self::SUCCESS;
        } catch (InvalidConfigException $e) {
            $this->error('❌ Configuration Error: ' . $e->getMessage());

            return self::FAILURE;
        } catch (ForgeApiException $e) {
            $this->error('❌ Forge API Error: ' . $e->getMessage());

            return self::FAILURE;
        } catch (\Throwable $e) {
            $this->error('❌ Unexpected Error: ' . $e->getMessage());
            $this->line('DEBUG: Exception caught: ' . $e->getMessage());
            $this->line('DEBUG: Exception class: ' . get_class($e));
            $this->line('DEBUG: Stack trace: ' . $e->getTraceAsString());

            return self::FAILURE;
        }
    }

    private function displayResults(array $result): void
    {
        if (! empty($result['created'])) {
            $this->info('📝 Created ' . count($result['created']) . ' heartbeat(s):');
            foreach ($result['created'] as $heartbeat) {
                $this->line("  • {$heartbeat->name}");
            }
        }

        if (! empty($result['updated'])) {
            $this->info('✏️  Updated ' . count($result['updated']) . ' heartbeat(s):');
            foreach ($result['updated'] as $heartbeat) {
                $this->line("  • {$heartbeat->name}");
            }
        }

        if (! empty($result['deleted'])) {
            $this->warn('🗑️  Deleted ' . count($result['deleted']) . ' heartbeat(s):');
            foreach ($result['deleted'] as $heartbeat) {
                $this->line("  • {$heartbeat->name}");
            }
        }

        if (empty($result['created']) && empty($result['updated']) && empty($result['deleted'])) {
            $this->info('✨ All heartbeats are already in sync');
        }
    }
}
