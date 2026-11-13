<?php

it('reads the default grace period from .env as an integer', function () {
    putenv('FORGE_HEARTBEAT_GRACE_PERIOD=10');

    try {
        $config = require __DIR__ . '/../../config/forge-heartbeats.php';
    } finally {
        putenv('FORGE_HEARTBEAT_GRACE_PERIOD');
    }

    expect($config['defaults']['grace_period'])->toBe(10);
});

it('reads ping_on_failure from .env', function (?string $value, bool $expected) {
    putenv($value === null ? 'FORGE_HEARTBEAT_PING_ON_FAILURE' : "FORGE_HEARTBEAT_PING_ON_FAILURE={$value}");

    try {
        $config = require __DIR__ . '/../../config/forge-heartbeats.php';
    } finally {
        putenv('FORGE_HEARTBEAT_PING_ON_FAILURE');
    }

    expect($config['ping_on_failure'])->toBe($expected);
})->with([
    'unset' => [null, true],
    'false' => ['false', false],
    'true' => ['true', true],
]);
