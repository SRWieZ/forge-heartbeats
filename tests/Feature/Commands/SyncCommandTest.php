<?php

use Illuminate\Console\Scheduling\Schedule;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use SRWieZ\ForgeHeartbeats\Http\Integrations\Forge\Requests\Heartbeats\CreateHeartbeatRequest;
use SRWieZ\ForgeHeartbeats\Http\Integrations\Forge\Requests\Heartbeats\ListHeartbeatsRequest;
use SRWieZ\ForgeHeartbeats\Http\Integrations\Forge\Requests\Heartbeats\UpdateHeartbeatRequest;
use SRWieZ\ForgeHeartbeats\Tests\TestClasses\TestKernel;

it('can sync scheduled tasks with heartbeats', function () {
    MockClient::global([
        ListHeartbeatsRequest::class => MockResponse::make(['data' => [
            [
                'id' => 1,
                'name' => 'inspire',
                'status' => 'pending',
                'grace_period' => 5,
                'frequency' => 0,
                'custom_frequency' => '0 * * * *',
                'ping_url' => 'https://forge.laravel.com/api/heartbeat/ping/test1',
            ],
        ]]),
        CreateHeartbeatRequest::class => MockResponse::make(['data' => [
            'id' => 2,
            'name' => 'new-command',
            'status' => 'pending',
            'grace_period' => 5,
            'frequency' => 0,
            'custom_frequency' => '0 0 * * *',
            'ping_url' => 'https://forge.laravel.com/api/heartbeat/ping/test2',
        ]], 201),
        UpdateHeartbeatRequest::class => MockResponse::make(['data' => [
            'id' => 1,
            'name' => 'inspire',
            'status' => 'pending',
            'grace_period' => 10,
            'frequency' => 0,
            'custom_frequency' => '0 * * * *',
            'ping_url' => 'https://forge.laravel.com/api/heartbeat/ping/test1',
        ]]),
    ]);

    TestKernel::registerScheduledTasks(function (Schedule $schedule) {
        $schedule->command('inspire')->cron('0 * * * *')->graceTimeInMinutes(10);
        $schedule->command('new-command')->cron('0 0 * * *');
    });

    $this->artisan('forge-heartbeats:sync')
        ->expectsOutput('🔍 Analyzing scheduled tasks...')
        ->expectsOutput('📋 Found 2 scheduled task(s) to monitor')
        ->expectsOutput('🔄 Syncing with Forge...')
        ->expectsOutput('✅ Sync completed successfully')
        ->assertExitCode(0);
});

it('can sync with keep-old flag', function () {
    MockClient::global([
        ListHeartbeatsRequest::class => MockResponse::make(['data' => [
            [
                'id' => 1,
                'name' => 'inspire',
                'status' => 'pending',
                'grace_period' => 5,
                'frequency' => 0,
                'custom_frequency' => '0 * * * *',
                'ping_url' => 'https://forge.laravel.com/api/heartbeat/ping/test1',
            ],
            [
                'id' => 2,
                'name' => 'orphaned-task',
                'status' => 'pending',
                'grace_period' => 10,
                'frequency' => 2,
                'custom_frequency' => '0 0 * * *',
                'ping_url' => 'https://forge.laravel.com/api/heartbeat/ping/test2',
            ],
        ]]),
    ]);

    TestKernel::registerScheduledTasks(function (Schedule $schedule) {
        $schedule->command('inspire')->cron('0 * * * *');
    });

    $this->artisan('forge-heartbeats:sync --keep-old')
        ->expectsOutput('🔍 Analyzing scheduled tasks...')
        ->expectsOutput('📋 Found 1 scheduled task(s) to monitor')
        ->expectsOutput('🔄 Syncing with Forge...')
        ->expectsOutput('✅ Sync completed successfully')
        ->assertExitCode(0);
});

it('handles empty scheduled tasks', function () {
    MockClient::global([
        ListHeartbeatsRequest::class => MockResponse::make(['data' => []]),
    ]);

    $this->artisan('forge-heartbeats:sync')
        ->expectsOutput('🔍 Analyzing scheduled tasks...')
        ->expectsOutput('⚠️  No scheduled tasks found to monitor.')
        ->assertExitCode(0);
});
