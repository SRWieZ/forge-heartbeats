<?php

use SRWieZ\ForgeHeartbeats\DTOs\ScheduledTask;

it('can create scheduled task from scheduler event', function () {
    $task = new ScheduledTask(
        name: 'inspire',
        cronExpression: '0 * * * *'
    );

    expect($task->name)->toBe('inspire');
    expect($task->cronExpression)->toBe('0 * * * *');
    expect($task->heartbeatName)->toBeNull();
    expect($task->graceTimeInMinutes)->toBeNull();
    expect($task->skipMonitoring)->toBeFalse();
});

it('extracts command name correctly', function () {
    // Create proper mock Event objects
    $event1 = Mockery::mock(\Illuminate\Console\Scheduling\Event::class);
    $event1->command = '/usr/bin/php /var/www/artisan inspire';
    $event1->expression = '0 * * * *';

    $task1 = ScheduledTask::fromSchedulerEvent($event1);
    expect($task1->name)->toBe('inspire');

    $event2 = Mockery::mock(\Illuminate\Console\Scheduling\Event::class);
    $event2->command = 'php artisan queue:work --stop-when-empty';
    $event2->expression = '0 * * * *';

    $task2 = ScheduledTask::fromSchedulerEvent($event2);
    expect($task2->name)->toBe('queue:work');
});

it('can get display name', function () {
    $task1 = new ScheduledTask(
        name: 'test-command',
        cronExpression: '0 * * * *'
    );
    expect($task1->getDisplayName())->toBe('test-command');

    $task2 = new ScheduledTask(
        name: 'test-command',
        cronExpression: '0 * * * *',
        heartbeatName: 'custom-name'
    );
    expect($task2->getDisplayName())->toBe('custom-name');
});

it('can check if should be monitored', function () {
    $task1 = new ScheduledTask(
        name: 'test-command',
        cronExpression: '0 * * * *'
    );
    expect($task1->shouldBeMonitored())->toBeTrue();

    $task2 = new ScheduledTask(
        name: 'test-command',
        cronExpression: '0 * * * *',
        skipMonitoring: true
    );
    expect($task2->shouldBeMonitored())->toBeFalse();
});
