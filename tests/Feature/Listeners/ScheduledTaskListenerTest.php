<?php

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

it('pings the heartbeat when a command task finishes', function () {
    Http::fake([
        self::FORGE_API_URL . '/' => Http::response(['data' => [[
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

    $event = app(Schedule::class)->command('inspire')->hourly();

    event(new ScheduledTaskFinished($event, 0.1));

    Http::assertSent(fn (Request $request) => $request->url() === 'https://forge.laravel.com/api/heartbeat/ping/test1');
});
