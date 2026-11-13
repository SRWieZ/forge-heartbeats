<?php

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Http;
use SRWieZ\ForgeHeartbeats\Tests\TestClasses\TestKernel;

it('can list heartbeats and tasks', function () {
    $this->fakeForgeApi([
        'GET /' => Http::response(['data' => [
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
    $this->fakeForgeApi([
        'GET /' => Http::response(['data' => []]),
    ]);

    $this->artisan('forge-heartbeats:list')
        ->expectsOutput('🔍 Fetching heartbeats from Forge...')
        ->assertExitCode(0);
});
