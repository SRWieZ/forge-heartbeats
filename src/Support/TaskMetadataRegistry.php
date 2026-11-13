<?php

namespace SRWieZ\ForgeHeartbeats\Support;

use Illuminate\Console\Scheduling\Event;
use InvalidArgumentException;

class TaskMetadataRegistry
{
    /**
     * Grace periods, in minutes, that the Forge API accepts.
     */
    private const GRACE_PERIODS = [1, 2, 5, 10, 30, 60];

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

    /**
     * @throws InvalidArgumentException when Forge does not accept the grace period
     */
    public static function setGraceTime(Event $event, int $minutes): void
    {
        if (! in_array($minutes, self::GRACE_PERIODS, true)) {
            throw new InvalidArgumentException(
                "graceTimeInMinutes({$minutes}): Forge heartbeats only accept a grace period of 1, 2, 5, 10, 30 or 60 minutes."
            );
        }

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
