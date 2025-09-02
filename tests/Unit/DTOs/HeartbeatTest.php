<?php

use SRWieZ\ForgeHeartbeats\DTOs\Heartbeat;

it('can create heartbeat from array', function () {
    $data = [
        'id' => '123',
        'attributes' => [
            'name' => 'test-heartbeat',
            'status' => 'pending',
            'grace_period' => 5,
            'frequency' => 1,
            'custom_frequency' => '0 * * * *',
            'ping_url' => 'https://forge.laravel.com/api/heartbeat/ping/abc123',
        ],
    ];

    $heartbeat = Heartbeat::fromArray($data);

    expect($heartbeat->id)->toBe(123);
    expect($heartbeat->name)->toBe('test-heartbeat');
    expect($heartbeat->status)->toBe('pending');
    expect($heartbeat->gracePeriod)->toBe(5);
    expect($heartbeat->frequency)->toBe(1);
    expect($heartbeat->customFrequency)->toBe('0 * * * *');
    expect($heartbeat->pingUrl)->toBe('https://forge.laravel.com/api/heartbeat/ping/abc123');
});

it('can create heartbeat from array without attributes wrapper', function () {
    $data = [
        'id' => '456',
        'name' => 'simple-heartbeat',
        'status' => 'up',
        'grace_period' => 10,
        'frequency' => 2,
        'custom_frequency' => null,
        'ping_url' => 'https://forge.laravel.com/api/heartbeat/ping/def456',
    ];

    $heartbeat = Heartbeat::fromArray($data);

    expect($heartbeat->id)->toBe(456);
    expect($heartbeat->name)->toBe('simple-heartbeat');
    expect($heartbeat->status)->toBe('up');
    expect($heartbeat->gracePeriod)->toBe(10);
    expect($heartbeat->frequency)->toBe(2);
    expect($heartbeat->customFrequency)->toBeNull();
    expect($heartbeat->pingUrl)->toBe('https://forge.laravel.com/api/heartbeat/ping/def456');
});

it('can convert heartbeat to array', function () {
    $heartbeat = new Heartbeat(
        id: 789,
        name: 'array-test',
        status: 'down',
        gracePeriod: 15,
        frequency: 3,
        customFrequency: '0 0 * * *',
        pingUrl: 'https://forge.laravel.com/api/heartbeat/ping/ghi789'
    );

    $array = $heartbeat->toArray();

    expect($array)->toBe([
        'id' => 789,
        'name' => 'array-test',
        'status' => 'down',
        'grace_period' => 15,
        'frequency' => 3,
        'custom_frequency' => '0 0 * * *',
        'ping_url' => 'https://forge.laravel.com/api/heartbeat/ping/ghi789',
    ]);
});