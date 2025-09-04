<?php

namespace SRWieZ\ForgeHeartbeats\DTOs;

class Heartbeat
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $status,
        public readonly int $gracePeriod,
        public readonly int $frequency,
        public readonly ?string $customFrequency,
        public readonly string $pingUrl
    ) {}

    public static function fromArray(array $data): self
    {
        $attributes = $data['attributes'] ?? $data;

        return new self(
            id: (int) $data['id'],
            name: $attributes['name'],
            status: $attributes['status'],
            gracePeriod: $attributes['grace_period'],
            frequency: $attributes['frequency'],
            customFrequency: $attributes['custom_frequency'] ?? null,
            pingUrl: $attributes['ping_url']
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'status' => $this->status,
            'grace_period' => $this->gracePeriod,
            'frequency' => $this->frequency,
            'custom_frequency' => $this->customFrequency,
            'ping_url' => $this->pingUrl,
        ];
    }
}
