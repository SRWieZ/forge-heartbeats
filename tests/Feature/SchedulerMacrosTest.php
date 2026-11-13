<?php

use Illuminate\Console\Scheduling\Schedule;
use SRWieZ\ForgeHeartbeats\Support\TaskMetadataRegistry;

it('accepts the grace periods Forge supports', function (int $minutes) {
    $event = app(Schedule::class)->command('inspire')->hourly()->graceTimeInMinutes($minutes);

    expect(TaskMetadataRegistry::getTaskMetadata($event)['grace_time'])->toBe($minutes);
})->with([1, 2, 5, 10, 30, 60]);

it('rejects grace periods Forge does not support', function (int $minutes) {
    app(Schedule::class)->command('inspire')->hourly()->graceTimeInMinutes($minutes);
})->with([0, 3, 15, 45, 120])->throws(
    InvalidArgumentException::class,
    'Forge heartbeats only accept a grace period of 1, 2, 5, 10, 30 or 60 minutes',
);
