<?php

namespace SRWieZ\ForgeHeartbeats\Tests\TestClasses;

use SRWieZ\ForgeHeartbeats\DTOs\Heartbeat;
use SRWieZ\ForgeHeartbeats\Contracts\ForgeClientInterface;
use SRWieZ\ForgeHeartbeats\Exceptions\InvalidConfigException;

class FakeForgeClient implements ForgeClientInterface
{
    private array $heartbeats = [];
    private int $nextId = 1;
    private bool $skipConfigValidation = false;
    
    public function skipConfigValidation(bool $skip = true): self
    {
        $this->skipConfigValidation = $skip;
        return $this;
    }
    
    public function listHeartbeats(): array
    {
        if (!$this->skipConfigValidation) {
            $this->validateConfig();
        }
        return array_values($this->heartbeats);
    }

    public function createHeartbeat(
        string $name,
        int $gracePeriod,
        int $frequency,
        ?string $customFrequency = null
    ): Heartbeat {
        if (!$this->skipConfigValidation) {
            $this->validateConfig();
        }
        $id = $this->nextId++;
        
        $heartbeat = new Heartbeat(
            id: $id,
            name: $name,
            status: 'pending',
            gracePeriod: $gracePeriod,
            frequency: $frequency,
            customFrequency: $customFrequency,
            pingUrl: "https://forge.laravel.com/api/heartbeat/ping/test{$id}"
        );
        
        $this->heartbeats[$id] = $heartbeat;
        
        return $heartbeat;
    }

    public function getHeartbeat(int $id): Heartbeat
    {
        if (!isset($this->heartbeats[$id])) {
            throw new \Exception("Heartbeat {$id} not found");
        }
        
        return $this->heartbeats[$id];
    }

    public function updateHeartbeat(
        int $id,
        string $name,
        int $gracePeriod,
        int $frequency,
        ?string $customFrequency = null
    ): Heartbeat {
        if (!isset($this->heartbeats[$id])) {
            throw new \Exception("Heartbeat {$id} not found");
        }
        
        $heartbeat = new Heartbeat(
            id: $id,
            name: $name,
            status: $this->heartbeats[$id]->status,
            gracePeriod: $gracePeriod,
            frequency: $frequency,
            customFrequency: $customFrequency,
            pingUrl: $this->heartbeats[$id]->pingUrl
        );
        
        $this->heartbeats[$id] = $heartbeat;
        
        return $heartbeat;
    }

    public function deleteHeartbeat(int $id): bool
    {
        if (!isset($this->heartbeats[$id])) {
            throw new \Exception("Heartbeat {$id} not found");
        }
        
        unset($this->heartbeats[$id]);
        
        return true;
    }

    public function pingHeartbeat(string $pingUrl): bool
    {
        // Simulate successful ping for test URLs
        return str_contains($pingUrl, 'test');
    }
    
    public function reset(): void
    {
        $this->heartbeats = [];
        $this->nextId = 1;
        $this->skipConfigValidation = false;
    }
    
    public function getHeartbeats(): array
    {
        return $this->heartbeats;
    }
    
    private function validateConfig(): void
    {
        $apiToken = config('forge-heartbeats.api_token');
        $organization = config('forge-heartbeats.organization');
        $serverId = config('forge-heartbeats.server_id');
        $siteId = config('forge-heartbeats.site_id');
        
        if (empty($apiToken)) {
            throw InvalidConfigException::missingApiToken();
        }
        
        if (empty($organization)) {
            throw InvalidConfigException::missingOrganization();
        }
        
        if (empty($serverId)) {
            throw InvalidConfigException::missingServerId();
        }
        
        if (empty($siteId)) {
            throw InvalidConfigException::missingSiteId();
        }
    }
}