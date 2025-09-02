<?php

use SRWieZ\ForgeHeartbeats\DTOs\Heartbeat;
use SRWieZ\ForgeHeartbeats\Contracts\ForgeClientInterface;

it('can list heartbeats', function () {
    $client = app(ForgeClientInterface::class);
    
    // Create a heartbeat in the fake client
    $client->createHeartbeat('test-heartbeat', 5, 1, '0 * * * *');
    
    $heartbeats = $client->listHeartbeats();

    expect($heartbeats)->toHaveCount(1);
    expect($heartbeats[0])->toBeInstanceOf(Heartbeat::class);
    expect($heartbeats[0]->name)->toBe('test-heartbeat');
});

it('can create heartbeat', function () {
    $client = app(ForgeClientInterface::class);
    
    $heartbeat = $client->createHeartbeat('new-heartbeat', 10, 2, '0 0 * * *');

    expect($heartbeat)->toBeInstanceOf(Heartbeat::class);
    expect($heartbeat->name)->toBe('new-heartbeat');
    expect($heartbeat->gracePeriod)->toBe(10);
    expect($heartbeat->frequency)->toBe(2);
    expect($heartbeat->customFrequency)->toBe('0 0 * * *');
});

it('can get specific heartbeat', function () {
    $client = app(ForgeClientInterface::class);
    
    // Create a heartbeat first
    $created = $client->createHeartbeat('specific-heartbeat', 15, 3, null);
    
    $heartbeat = $client->getHeartbeat($created->id);

    expect($heartbeat)->toBeInstanceOf(Heartbeat::class);
    expect($heartbeat->id)->toBe($created->id);
    expect($heartbeat->name)->toBe('specific-heartbeat');
    expect($heartbeat->status)->toBe('pending');
});

it('can update heartbeat', function () {
    $client = app(ForgeClientInterface::class);
    
    // Create a heartbeat first
    $created = $client->createHeartbeat('original-heartbeat', 10, 2, '0 * * * *');
    
    $heartbeat = $client->updateHeartbeat($created->id, 'updated-heartbeat', 20, 1, '*/30 * * * *');

    expect($heartbeat->name)->toBe('updated-heartbeat');
    expect($heartbeat->gracePeriod)->toBe(20);
});

it('can delete heartbeat', function () {
    $client = app(ForgeClientInterface::class);
    
    // Create a heartbeat first
    $created = $client->createHeartbeat('to-delete', 5, 1, '0 * * * *');
    
    $result = $client->deleteHeartbeat($created->id);

    expect($result)->toBeTrue();
    
    // Verify it's deleted by checking list doesn't contain it
    $heartbeats = $client->listHeartbeats();
    expect($heartbeats)->toHaveCount(0);
});

it('can ping heartbeat', function () {
    $client = app(ForgeClientInterface::class);
    
    $result = $client->pingHeartbeat('https://forge.laravel.com/api/heartbeat/ping/test123');

    expect($result)->toBeTrue();
});

it('throws exception when heartbeat not found', function () {
    $client = app(ForgeClientInterface::class);

    expect(fn () => $client->getHeartbeat(999))
        ->toThrow(Exception::class);
});

it('handles ping failures gracefully', function () {
    $client = app(ForgeClientInterface::class);

    // Fake client returns false for non-test URLs
    $result = $client->pingHeartbeat('https://forge.laravel.com/api/heartbeat/ping/invalid');

    expect($result)->toBeFalse();
});