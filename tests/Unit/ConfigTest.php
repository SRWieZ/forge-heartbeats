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
