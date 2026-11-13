<?php

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use SRWieZ\ForgeHeartbeats\Tests\TestClasses\TestKernel;

it('can sync scheduled tasks with heartbeats', function () {
    $this->fakeForgeApi([
        'GET /' => Http::response(['data' => [
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
        'POST /' => Http::response(['data' => [
            'id' => 2,
            'name' => 'new-command',
            'status' => 'pending',
            'grace_period' => 5,
            'frequency' => 0,
            'custom_frequency' => '0 0 * * *',
            'ping_url' => 'https://forge.laravel.com/api/heartbeat/ping/test2',
        ]], 201),
        'PUT /1' => Http::response(['data' => [
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

    Http::assertSent(fn (Request $request) => $request->method() === 'POST'
        && $request->hasHeader('Authorization', 'Bearer test-token')
        && $request->data() === ['name' => 'new-command', 'grace_period' => 5, 'frequency' => 1440]);

    Http::assertSent(fn (Request $request) => $request->method() === 'PUT'
        && $request->hasHeader('Authorization', 'Bearer test-token')
        && $request->data() === ['name' => 'inspire', 'grace_period' => 10, 'frequency' => 60]);
});

it('can sync with keep-old flag', function () {
    $this->fakeForgeApi([
        'GET /' => Http::response(['data' => [
            [
                'id' => 1,
                'name' => 'inspire',
                'status' => 'pending',
                'grace_period' => 5,
                'frequency' => 60,
                'custom_frequency' => null,
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
        ->expectsOutput('✨ All heartbeats are already in sync')
        ->expectsOutput('✅ Sync completed successfully')
        ->assertExitCode(0);

    Http::assertNotSent(fn (Request $request) => $request->method() === 'DELETE');
});

it('deletes orphaned heartbeats without keep-old flag', function () {
    $this->fakeForgeApi([
        'GET /' => Http::response(['data' => [
            [
                'id' => 1,
                'name' => 'inspire',
                'status' => 'pending',
                'grace_period' => 5,
                'frequency' => 60,
                'custom_frequency' => null,
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
        'DELETE /2' => Http::response(null, 204),
    ]);

    TestKernel::registerScheduledTasks(function (Schedule $schedule) {
        $schedule->command('inspire')->cron('0 * * * *');
    });

    $this->artisan('forge-heartbeats:sync')
        ->expectsOutput('🔍 Analyzing scheduled tasks...')
        ->expectsOutput('📋 Found 1 scheduled task(s) to monitor')
        ->expectsOutput('🔄 Syncing with Forge...')
        ->expectsOutput('🗑️  Deleted 1 heartbeat(s):')
        ->expectsOutput('✅ Sync completed successfully')
        ->assertExitCode(0);

    Http::assertSent(fn (Request $request) => $request->method() === 'DELETE'
        && $request->url() === 'https://forge.laravel.com/api/orgs/test-org/servers/12345/sites/67890/heartbeats/2');
});

it('handles empty scheduled tasks', function () {
    $this->fakeForgeApi([
        'GET /' => Http::response(['data' => []]),
    ]);

    $this->artisan('forge-heartbeats:sync')
        ->expectsOutput('🔍 Analyzing scheduled tasks...')
        ->expectsOutput('⚠️  No scheduled tasks found to monitor.')
        ->assertExitCode(0);
});
