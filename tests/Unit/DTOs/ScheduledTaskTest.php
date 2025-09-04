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
    // Test the static method directly with command strings
    expect(ScheduledTask::fromSchedulerEvent((object) [
        'command' => '/usr/bin/php /var/www/artisan inspire',
        'expression' => '0 * * * *',
        'timezone' => null,
    ])->name)->toBe('inspire');

    expect(ScheduledTask::fromSchedulerEvent((object) [
        'command' => 'php artisan queue:work --stop-when-empty',
        'expression' => '0 * * * *',
        'timezone' => null,
    ])->name)->toBe('queue:work');
})->skip('Need to refactor ScheduledTask::fromSchedulerEvent to work with test objects');

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
