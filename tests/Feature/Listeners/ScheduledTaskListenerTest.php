<?php

use Illuminate\Console\Events\ScheduledBackgroundTaskFinished;
use Illuminate\Console\Events\ScheduledTaskFailed;
use Illuminate\Console\Events\ScheduledTaskFinished;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use SRWieZ\ForgeHeartbeats\Tests\TestClasses\TestKernel;

it('does not break schedule:run when closure tasks finish', function () {
    Http::fake();

    $ran = [];

    TestKernel::registerScheduledTasks(function (Schedule $schedule) use (&$ran) {
        $schedule->call(function () use (&$ran) {
            $ran[] = 'first';
        })->everyMinute();

        $schedule->call(function () use (&$ran) {
            $ran[] = 'second';
        })->everyMinute();
    });

    $this->artisan('schedule:run')->assertExitCode(0);

    expect($ran)->toBe(['first', 'second']);
    Http::assertNothingSent();
});

function fakeInspireHeartbeat(): void
{
    Http::fake([
        'https://forge.laravel.com/api/orgs/test-org/servers/12345/sites/67890/heartbeats/' => Http::response(['data' => [[
            'id' => 1,
            'name' => 'inspire',
            'status' => 'beating',
            'grace_period' => 5,
            'frequency' => 60,
            'custom_frequency' => null,
            'ping_url' => 'https://forge.laravel.com/api/heartbeat/ping/test1',
        ]]]),
        'forge.laravel.com/api/heartbeat/ping/*' => Http::response(),
    ]);
}

function pingCount(): int
{
    return Http::recorded(fn (Request $request) => $request->url() === 'https://forge.laravel.com/api/heartbeat/ping/test1')->count();
}

it('pings the heartbeat when a command task finishes', function () {
    fakeInspireHeartbeat();

    $event = app(Schedule::class)->command('inspire')->hourly();
    $event->exitCode = 0;

    event(new ScheduledTaskFinished($event, 0.1));

    expect(pingCount())->toBe(1);
});

it('pings failed tasks by default', function () {
    fakeInspireHeartbeat();

    $event = app(Schedule::class)->command('inspire')->hourly();
    $event->exitCode = 1;

    // What schedule:run dispatches for a command that exits non-zero
    event(new ScheduledTaskFinished($event, 0.1));
    event(new ScheduledTaskFailed($event, new Exception('Scheduled command [inspire] failed with exit code [1].')));

    expect(pingCount())->toBe(2);
});

it('does not ping failed tasks when ping_on_failure is disabled', function () {
    config()->set('forge-heartbeats.ping_on_failure', false);
    fakeInspireHeartbeat();

    $event = app(Schedule::class)->command('inspire')->hourly();
    $event->exitCode = 1;

    event(new ScheduledTaskFinished($event, 0.1));
    event(new ScheduledTaskFailed($event, new Exception('Scheduled command [inspire] failed with exit code [1].')));
    event(new ScheduledBackgroundTaskFinished($event));

    expect(pingCount())->toBe(0);
});

it('still pings successful tasks when ping_on_failure is disabled', function () {
    config()->set('forge-heartbeats.ping_on_failure', false);
    fakeInspireHeartbeat();

    $event = app(Schedule::class)->command('inspire')->hourly();
    $event->exitCode = 0;

    event(new ScheduledTaskFinished($event, 0.1));
    event(new ScheduledBackgroundTaskFinished($event));

    expect(pingCount())->toBe(2);
});
