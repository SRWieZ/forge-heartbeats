<?php

namespace SRWieZ\ForgeHeartbeats\Http\Client;

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
     * Treat any once-per-day cron as DAILY.
     * Returns CUSTOM if the cron doesn't match a standard pattern.
     */
    public static function fromCronExpression(string $cronExpression): self
    {
        // Treat any once-per-day cron as DAILY.
        // Example: 0 10 * * * (which is 10 am everyday)

        // Also matches the following:
        
        // 0 8 * * *
        // 0 12 * * *
        // 0 12,18 * * *
        // 0 8,10,12,20 * * *

        if (preg_match('/^0 ((?:[01]?\d|2[0-3])(?:,(?:[01]?\d|2[0-3]))*) \* \* \*$/', $cronExpression) === 1) {
            return self::DAILY;
        }
        
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
