<?php

use Illuminate\Console\Scheduling\Schedule;
use SRWieZ\ForgeHeartbeats\Http\Client\ForgeClientInterface;
use SRWieZ\ForgeHeartbeats\Tests\TestClasses\TestKernel;

it('can list heartbeats and tasks', function () {
    $client = app(ForgeClientInterface::class);

    // Create some heartbeats
    $client->createHeartbeat('inspire', 5, 1, '0 * * * *');
    $client->createHeartbeat('orphaned-task', 10, 2, '0 0 * * *');

    TestKernel::registerScheduledTasks(function (Schedule $schedule) {
        $schedule->command('inspire')->cron('0 * * * *');
    });

    $this->artisan('forge-heartbeats:list')
        ->expectsOutput('🔍 Fetching heartbeats from Forge...')
        ->assertExitCode(0);
});

it('handles empty heartbeats and tasks', function () {
    $this->artisan('forge-heartbeats:list')
        ->expectsOutput('🔍 Fetching heartbeats from Forge...')
        ->assertExitCode(0);
});

it('handles configuration errors gracefully', function () {
    $client = app(ForgeClientInterface::class);
    $client->skipConfigValidation(false); // Enable validation for this test

    config(['forge-heartbeats.api_token' => null]);

    $this->artisan('forge-heartbeats:list')
        ->expectsOutput('🔍 Fetching heartbeats from Forge...')
        ->assertExitCode(1);
});
