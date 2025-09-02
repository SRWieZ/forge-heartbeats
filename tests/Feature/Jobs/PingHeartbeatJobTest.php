<?php

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
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

    $job->handle(app('SRWieZ\ForgeHeartbeats\Contracts\ForgeClientInterface'));

    Event::assertDispatched(HeartbeatPinged::class, function ($event) {
        return $event->taskName === 'test-task' &&
               $event->success === true &&
               $event->eventType === 'finished';
    });

    Log::shouldHaveReceived('debug')->with('Successfully pinged heartbeat for task: test-task');
});

it('handles ping failures', function () {
    $job = new PingHeartbeatJob(
        'https://forge.laravel.com/api/heartbeat/ping/invalid-url',
        'test-task',
        'finished'
    );

    $job->handle(app('SRWieZ\ForgeHeartbeats\Contracts\ForgeClientInterface'));

    Event::assertDispatched(HeartbeatPinged::class, function ($event) {
        return $event->taskName === 'test-task' &&
               $event->success === false &&
               $event->eventType === 'finished';
    });

    Log::shouldHaveReceived('warning')->with('Failed to ping heartbeat for task: test-task');
});

it('handles exceptions and retries', function () {
    $client = Mockery::mock('SRWieZ\ForgeHeartbeats\Contracts\ForgeClientInterface');
    $client->shouldReceive('pingHeartbeat')
           ->andThrow(new Exception('Network error'));

    $job = new PingHeartbeatJob(
        'https://forge.laravel.com/api/heartbeat/ping/test123',
        'test-task',
        'finished'
    );

    expect(fn () => $job->handle($client))->toThrow(Exception::class);

    Log::shouldHaveReceived('error')->with('Error pinging heartbeat for task test-task: Network error');
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