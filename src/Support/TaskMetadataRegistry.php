<?php

namespace SRWieZ\ForgeHeartbeats\Support;

use Illuminate\Console\Scheduling\Event;

class TaskMetadataRegistry
{
    private static array $metadata = [];

    private static function getEventKey(Event $event): string
    {
        // Create a unique key based on command and expression
        return md5($event->command . '|' . $event->expression);
    }

    private static function setMetadata(Event $event, string $key, mixed $value): void
    {
        $eventKey = self::getEventKey($event);
        self::$metadata[$eventKey][$key] = $value;
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
        $eventKey = self::getEventKey($event);

        return self::$metadata[$eventKey] ?? [];
    }

    public static function clear(): void
    {
        self::$metadata = [];
    }
}
