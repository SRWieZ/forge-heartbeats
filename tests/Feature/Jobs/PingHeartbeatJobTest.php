<?php

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use SRWieZ\ForgeHeartbeats\Events\HeartbeatPinged;
use SRWieZ\ForgeHeartbeats\Jobs\PingHeartbeatJob;

beforeEach(function () {
    Event::fake();
    Log::spy();
    Http::fake([
        'forge.laravel.com/api/heartbeat/ping/invalid-url' => Http::response(null, 500),
        'forge.laravel.com/api/heartbeat/ping/*' => Http::response(),
    ]);
});

it('can ping heartbeat successfully', function () {
    $job = new PingHeartbeatJob(
        'https://forge.laravel.com/api/heartbeat/ping/test123',
        'test-task',
        'finished'
    );

    $job->handle();

    Http::assertSent(fn ($request) => $request->method() === 'GET'
        && $request->url() === 'https://forge.laravel.com/api/heartbeat/ping/test123');

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

function queuedPayload(PingHeartbeatJob $job): array
{
    $queue = app('queue')->connection('sync');

    return json_decode((fn () => $this->createPayload($job, 'default'))->call($queue), true);
}

it('waits queue.backoff seconds before retrying a failed ping', function () {
    config()->set('forge-heartbeats.queue.backoff', 15);

    $payload = queuedPayload(new PingHeartbeatJob('https://forge.laravel.com/api/heartbeat/ping/test123', 'test-task'));

    expect($payload['backoff'])->toBe('15');
});

it('falls back to queue.retry_after when queue.backoff is not set', function () {
    // A config published before 2.0 has no queue.backoff key
    config()->set('forge-heartbeats.queue', [
        'connection' => 'sync',
        'name' => 'default',
        'retry_after' => 30,
        'max_attempts' => 3,
    ]);

    $payload = queuedPayload(new PingHeartbeatJob('https://forge.laravel.com/api/heartbeat/ping/test123', 'test-task'));

    expect($payload['backoff'])->toBe('30');
});
