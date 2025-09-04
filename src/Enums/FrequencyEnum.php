<?php

namespace SRWieZ\ForgeHeartbeats\Enums;

enum FrequencyEnum: int
{
    case EVERY_MINUTE = 1;
    case EVERY_5_MINUTES = 5;
    case EVERY_10_MINUTES = 10;
    case EVERY_30_MINUTES = 30;
    case EVERY_HOUR = 60;
    case DAILY = 1440;
    case WEEKLY = 10080;
    case MONTHLY = 312480;
    case CUSTOM = -1;

    /**
     * Map a cron expression to a frequency.
     * Returns CUSTOM if the cron doesn't match a standard pattern.
     */
    public static function fromCronExpression(string $cronExpression): self
    {
        return match ($cronExpression) {
            '* * * * *' => self::EVERY_MINUTE,
            '*/5 * * * *' => self::EVERY_5_MINUTES,
            '*/10 * * * *' => self::EVERY_10_MINUTES,
            '*/30 * * * *' => self::EVERY_30_MINUTES,
            '0 * * * *' => self::EVERY_HOUR,
            '0 0 * * *' => self::DAILY,
            '0 0 * * 0' => self::WEEKLY,
            '0 0 1 * *' => self::MONTHLY,
            default => self::CUSTOM,
        };
    }
}
