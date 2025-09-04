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
use SRWieZ\ForgeHeartbeats\Contracts\ForgeClientInterface;
use SRWieZ\ForgeHeartbeats\Http\ForgeClient;
use SRWieZ\ForgeHeartbeats\Jobs\PingHeartbeatJob;
use SRWieZ\ForgeHeartbeats\Listeners\ScheduledTaskSubscriber;
use SRWieZ\ForgeHeartbeats\Support\HeartbeatManager;
use SRWieZ\ForgeHeartbeats\Support\ScheduleAnalyzer;
use SRWieZ\ForgeHeartbeats\Support\TaskMatcher;

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
        $this->app->bind(ForgeClientInterface::class, ForgeClient::class);
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
        ], ScheduledTaskSubscriber::class);

        return $this;
    }

    protected function registerSchedulerEventMacros(): self
    {
        SchedulerEvent::macro('heartbeatName', function (string $name) {
            return $this->then(function () use ($name) {
                app(HeartbeatManager::class)->setHeartbeatName($this, $name);
            });
        });

        SchedulerEvent::macro('graceTimeInMinutes', function (int $minutes) {
            return $this->then(function () use ($minutes) {
                app(HeartbeatManager::class)->setGraceTime($this, $minutes);
            });
        });

        SchedulerEvent::macro('doNotMonitorOnForge', function (bool $skip = true) {
            return $this->then(function () use ($skip) {
                app(HeartbeatManager::class)->setSkipMonitoring($this, $skip);
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
}
