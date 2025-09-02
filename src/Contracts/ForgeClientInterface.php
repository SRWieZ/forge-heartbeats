<?php

namespace SRWieZ\ForgeHeartbeats\Contracts;

use SRWieZ\ForgeHeartbeats\DTOs\Heartbeat;

interface ForgeClientInterface
{
    /**
     * List all heartbeats for the configured site.
     *
     * @return array<Heartbeat>
     */
    public function listHeartbeats(): array;

    /**
     * Create a new heartbeat.
     */
    public function createHeartbeat(string $name, int $gracePeriod, int $frequency, ?string $customFrequency = null): Heartbeat;

    /**
     * Get a specific heartbeat by ID.
     */
    public function getHeartbeat(int $heartbeatId): Heartbeat;

    /**
     * Update an existing heartbeat.
     */
    public function updateHeartbeat(int $heartbeatId, string $name, int $gracePeriod, int $frequency, ?string $customFrequency = null): Heartbeat;

    /**
     * Delete a heartbeat.
     */
    public function deleteHeartbeat(int $heartbeatId): bool;

    /**
     * Ping a heartbeat URL.
     */
    public function pingHeartbeat(string $pingUrl): bool;
}