<?php

namespace SRWieZ\ForgeHeartbeats\Support;

use Illuminate\Console\Scheduling\Event;
use WeakMap;

class TaskMetadataRegistry
{
    private static WeakMap $metadata;

    private static function getRegistry(): WeakMap
    {
        return self::$metadata ??= new WeakMap;
    }

    private static function setMetadata(Event $event, string $key, mixed $value): void
    {
        $registry = self::getRegistry();
        $eventMetadata = $registry[$event] ?? [];
        $eventMetadata[$key] = $value;
        $registry[$event] = $eventMetadata;
    }

    public static function setHeartbeatName(Event $event, string $name): void
    {
        self::setMetadata($event, 'heartbeat_name', $name);
    }

    public static function setGraceTime(Event $event, int $minutes): void
    {
        self::setMetadata($event, 'grace_time', $minutes);
    }

    public static function setSkipMonitoring(Event $event, bool $skip): void
    {
        self::setMetadata($event, 'skip_monitoring', $skip);
    }

    public static function getTaskMetadata(Event $event): array
    {
        $registry = self::getRegistry();

        return $registry[$event] ?? [];
    }

    public static function clear(): void
    {
        self::$metadata = new WeakMap;
    }
}
