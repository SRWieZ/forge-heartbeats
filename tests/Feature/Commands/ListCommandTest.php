<?php

use Illuminate\Console\Scheduling\Schedule;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use SRWieZ\ForgeHeartbeats\Http\Integrations\Forge\Requests\Heartbeats\ListHeartbeatsRequest;
use SRWieZ\ForgeHeartbeats\Tests\TestClasses\TestKernel;

it('can list heartbeats and tasks', function () {
    MockClient::global([
        ListHeartbeatsRequest::class => MockResponse::make(['data' => [
            [
                'id' => 1,
                'name' => 'inspire',
                'status' => 'pending',
                'grace_period' => 5,
                'frequency' => 1,
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

    $this->artisan('forge-heartbeats:list')
        ->expectsOutput('🔍 Fetching heartbeats from Forge...')
        ->assertExitCode(0);
});

it('handles empty heartbeats and tasks', function () {
    MockClient::global([
        ListHeartbeatsRequest::class => MockResponse::make(['data' => []]),
    ]);

    $this->artisan('forge-heartbeats:list')
        ->expectsOutput('🔍 Fetching heartbeats from Forge...')
        ->assertExitCode(0);
});
