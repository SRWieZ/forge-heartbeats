<?php

use SRWieZ\ForgeHeartbeats\DTOs\ScheduledTask;

it('can create scheduled task from scheduler event', function () {
    $task = new ScheduledTask(
        name: 'inspire',
        cronExpression: '0 * * * *',
        timezone: 'UTC'
    );

    expect($task->name)->toBe('inspire');
    expect($task->cronExpression)->toBe('0 * * * *');
    expect($task->timezone)->toBe('UTC');
    expect($task->heartbeatName)->toBeNull();
    expect($task->graceTimeInMinutes)->toBeNull();
    expect($task->skipMonitoring)->toBeFalse();
});

it('extracts command name correctly', function () {
    // Create proper mock Event objects
    $event1 = Mockery::mock(\Illuminate\Console\Scheduling\Event::class);
    $event1->command = '/usr/bin/php /var/www/artisan inspire';
    $event1->expression = '0 * * * *';
    $event1->timezone = null;

    $task1 = ScheduledTask::fromSchedulerEvent($event1);
    expect($task1->name)->toBe('inspire');

    $event2 = Mockery::mock(\Illuminate\Console\Scheduling\Event::class);
    $event2->command = 'php artisan queue:work --stop-when-empty';
    $event2->expression = '0 * * * *';
    $event2->timezone = null;

    $task2 = ScheduledTask::fromSchedulerEvent($event2);
    expect($task2->name)->toBe('queue:work');
});

it('can get display name', function () {
    $task1 = new ScheduledTask(
        name: 'test-command',
        cronExpression: '0 * * * *',
        timezone: null
    );
    expect($task1->getDisplayName())->toBe('test-command');

    $task2 = new ScheduledTask(
        name: 'test-command',
        cronExpression: '0 * * * *',
        timezone: null,
        heartbeatName: 'custom-name'
    );
    expect($task2->getDisplayName())->toBe('custom-name');
});

it('can check if should be monitored', function () {
    $task1 = new ScheduledTask(
        name: 'test-command',
        cronExpression: '0 * * * *',
        timezone: null
    );
    expect($task1->shouldBeMonitored())->toBeTrue();

    $task2 = new ScheduledTask(
        name: 'test-command',
        cronExpression: '0 * * * *',
        timezone: null,
        skipMonitoring: true
    );
    expect($task2->shouldBeMonitored())->toBeFalse();
});
