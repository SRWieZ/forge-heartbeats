<?php

namespace SRWieZ\ForgeHeartbeats;

use Illuminate\Console\Events\ScheduledBackgroundTaskFinished;
use Illuminate\Console\Events\ScheduledTaskFailed;
use Illuminate\Console\Events\ScheduledTaskFinished;
use Illuminate\Console\Scheduling\Event as SchedulerEvent;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Event;
use Laravel\Horizon\Horizon;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use SRWieZ\ForgeHeartbeats\Commands\ListCommand;
use SRWieZ\ForgeHeartbeats\Commands\SyncCommand;
use SRWieZ\ForgeHeartbeats\Commands\VerifyCommand;
use SRWieZ\ForgeHeartbeats\Http\Client\Exceptions\InvalidConfigException;
use SRWieZ\ForgeHeartbeats\Http\Integrations\Forge\ForgeHeartbeatsConnector;
use SRWieZ\ForgeHeartbeats\Jobs\PingHeartbeatJob;
use SRWieZ\ForgeHeartbeats\Listeners\ScheduledTaskListener;
use SRWieZ\ForgeHeartbeats\Support\HeartbeatManager;
use SRWieZ\ForgeHeartbeats\Support\ScheduleAnalyzer;
use SRWieZ\ForgeHeartbeats\Support\TaskMatcher;
use SRWieZ\ForgeHeartbeats\Support\TaskMetadataRegistry;

class ForgeHeartbeatsServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('forge-heartbeats')
            ->hasConfigFile()
            ->hasViews()
            ->hasCommands([
                ListCommand::class,
                SyncCommand::class,
                VerifyCommand::class,
            ]);
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(ForgeHeartbeatsConnector::class, function () {
            // Only validate config in non-testing environments
            if (! app()->environment('testing')) {
                $this->validateConfig();
            }

            return new ForgeHeartbeatsConnector(
                apiToken: config('forge-heartbeats.api_token') ?: '',
                organization: config('forge-heartbeats.organization') ?: '',
                serverId: (int) config('forge-heartbeats.server_id', 0),
                siteId: (int) config('forge-heartbeats.site_id', 0),
            );
        });

        $this->app->singleton(HeartbeatManager::class);
        $this->app->singleton(ScheduleAnalyzer::class, function ($app) {
            // Use the singleton instance of Schedule
            return new ScheduleAnalyzer($app[Schedule::class]);
        });
        $this->app->singleton(TaskMatcher::class);
    }

    public function packageBooted(): void
    {
        $this
            ->registerEventHandlers()
            ->registerSchedulerEventMacros()
            ->silenceHorizonJobs();
    }

    protected function registerEventHandlers(): self
    {
        Event::listen([
            ScheduledTaskFinished::class,
            ScheduledBackgroundTaskFinished::class,
            ScheduledTaskFailed::class,
        ], ScheduledTaskListener::class);

        return $this;
    }

    protected function registerSchedulerEventMacros(): self
    {
        SchedulerEvent::macro('heartbeatName', function (string $name) {
            return $this->then(function () use ($name) {
                TaskMetadataRegistry::setHeartbeatName($this, $name);
            });
        });

        SchedulerEvent::macro('graceTimeInMinutes', function (int $minutes) {
            return $this->then(function () use ($minutes) {
                TaskMetadataRegistry::setGraceTime($this, $minutes);
            });
        });

        SchedulerEvent::macro('doNotMonitorOnForge', function (bool $skip = true) {
            return $this->then(function () use ($skip) {
                TaskMetadataRegistry::setSkipMonitoring($this, $skip);
            });
        });

        return $this;
    }

    protected function silenceHorizonJobs(): self
    {
        if (! config('forge-heartbeats.horizon.silence_ping_jobs', true)) {
            return $this;
        }

        if (! class_exists(Horizon::class)) {
            return $this;
        }

        $silencedJobs = config('horizon.silenced', []);

        if (! in_array(PingHeartbeatJob::class, $silencedJobs)) {
            $silencedJobs[] = PingHeartbeatJob::class;
            config()->set('horizon.silenced', $silencedJobs);
        }

        return $this;
    }

    private function validateConfig(): void
    {
        $apiToken = config('forge-heartbeats.api_token');
        $organization = config('forge-heartbeats.organization');
        $serverId = config('forge-heartbeats.server_id');
        $siteId = config('forge-heartbeats.site_id');

        if (empty($apiToken)) {
            throw InvalidConfigException::missingApiToken();
        }

        if (empty($organization)) {
            throw InvalidConfigException::missingOrganization();
        }

        if (empty($serverId)) {
            throw InvalidConfigException::missingServerId();
        }

        if (empty($siteId)) {
            throw InvalidConfigException::missingSiteId();
        }
    }
}
