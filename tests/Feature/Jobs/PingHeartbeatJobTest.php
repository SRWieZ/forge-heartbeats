<?php

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use SRWieZ\ForgeHeartbeats\Events\HeartbeatPinged;
use SRWieZ\ForgeHeartbeats\Jobs\PingHeartbeatJob;

beforeEach(function () {
    Event::fake();
    Log::spy();
});

it('can ping heartbeat successfully', function () {
    $job = new PingHeartbeatJob(
        'https://forge.laravel.com/api/heartbeat/ping/test123',
        'test-task',
        'finished'
    );

    $job->handle();

    Event::assertDispatched(HeartbeatPinged::class, function ($event) {
        return $event->taskName === 'test-task' &&
               $event->success === true &&
               $event->eventType === 'finished';
    });
});

it('handles ping failures', function () {
    $job = new PingHeartbeatJob(
        'https://forge.laravel.com/api/heartbeat/ping/invalid-url',
        'test-task',
        'finished'
    );

    expect(fn () => $job->handle())
        ->toThrow(Exception::class, 'Failed to ping heartbeat URL: https://forge.laravel.com/api/heartbeat/ping/invalid-url');
});

it('handles job failure after retries', function () {
    $job = new PingHeartbeatJob(
        'https://forge.laravel.com/api/heartbeat/ping/test123',
        'test-task',
        'finished'
    );

    $exception = new Exception('Final failure');
    $job->failed($exception);

    Event::assertDispatched(HeartbeatPinged::class, function ($event) {
        return $event->taskName === 'test-task' &&
               $event->success === false &&
               $event->error === 'Final failure';
    });
});
