<?php

use SRWieZ\ForgeHeartbeats\DTOs\Heartbeat;
use SRWieZ\ForgeHeartbeats\DTOs\ScheduledTask;
use SRWieZ\ForgeHeartbeats\Support\TaskMatcher;

beforeEach(function () {
    $this->matcher = new TaskMatcher;
});

it('can match tasks to heartbeats', function () {
    $tasks = [
        new ScheduledTask('task-1', '0 * * * *'),
        new ScheduledTask('task-2', '0 0 * * *'),
        new ScheduledTask('task-3', '0 */6 * * *'),
    ];

    $heartbeats = [
        new Heartbeat(1, 'task-1', 'pending', 5, 1, '0 * * * *', 'http://ping1'),
        new Heartbeat(2, 'task-2', 'up', 10, 2, '0 0 * * *', 'http://ping2'),
        new Heartbeat(3, 'orphaned-task', 'down', 15, 3, '0 12 * * *', 'http://ping3'),
    ];

    $result = $this->matcher->match($tasks, $heartbeats);

    expect($result['matched'])->toHaveCount(2);
    expect($result['matched']['task-1']['task']->name)->toBe('task-1');
    expect($result['matched']['task-1']['heartbeat']->name)->toBe('task-1');
    expect($result['matched']['task-2']['task']->name)->toBe('task-2');
    expect($result['matched']['task-2']['heartbeat']->name)->toBe('task-2');

    expect($result['unmatched_tasks'])->toHaveCount(1);
    expect($result['unmatched_tasks'][0]->name)->toBe('task-3');

    expect($result['orphaned_heartbeats'])->toHaveCount(1);
    expect($result['orphaned_heartbeats'][0]->name)->toBe('orphaned-task');
});

it('can find heartbeat for task', function () {
    $task = new ScheduledTask('test-task', '0 * * * *');

    $heartbeats = [
        new Heartbeat(1, 'other-task', 'pending', 5, 1, '0 * * * *', 'http://ping1'),
        new Heartbeat(2, 'test-task', 'up', 10, 2, '0 0 * * *', 'http://ping2'),
    ];

    $found = $this->matcher->findHeartbeatForTask($task, $heartbeats);

    expect($found)->not->toBeNull();
    expect($found->name)->toBe('test-task');
    expect($found->id)->toBe(2);
});

it('returns null when heartbeat not found', function () {
    $task = new ScheduledTask('missing-task', '0 * * * *');

    $heartbeats = [
        new Heartbeat(1, 'other-task', 'pending', 5, 1, '0 * * * *', 'http://ping1'),
    ];

    $found = $this->matcher->findHeartbeatForTask($task, $heartbeats);

    expect($found)->toBeNull();
});

it('handles tasks with custom heartbeat names', function () {
    $tasks = [
        new ScheduledTask('command-name', '0 * * * *', 'custom-heartbeat-name'),
    ];

    $heartbeats = [
        new Heartbeat(1, 'custom-heartbeat-name', 'pending', 5, 1, '0 * * * *', 'http://ping1'),
    ];

    $result = $this->matcher->match($tasks, $heartbeats);

    expect($result['matched'])->toHaveCount(1);
    expect($result['matched']['custom-heartbeat-name']['task']->name)->toBe('command-name');
    expect($result['matched']['custom-heartbeat-name']['heartbeat']->name)->toBe('custom-heartbeat-name');
});
