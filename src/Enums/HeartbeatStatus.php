<?php

namespace SRWieZ\ForgeHeartbeats\Enums;

enum HeartbeatStatus: string
{
    case PENDING = 'pending';
    case BEATING = 'beating';
    case MISSING = 'missing';

    public function icon(): string
    {
        return match ($this) {
            self::PENDING => '⏳',
            self::BEATING => '💚',
            self::MISSING => '⚠️',
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::BEATING => 'Beating',
            self::MISSING => 'Missing',
        };
    }

    public function displayText(): string
    {
        return $this->icon() . ' ' . $this->label();
    }
}
