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
     * Map a cron expression to a frequency. Returns CUSTOM if the cron doesn't match a standard pattern.
     *
     * DAILY covers every cron that runs at a fixed minute, every day, at one hour or at an explicit
     * list of hours: "0 0 * * *", "30 8 * * *" (dailyAt('8:30')), "0 1,13 * * *" (twiceDaily()).
     * A schedule that runs several times a day this way is still monitored with Forge's 24-hour
     * window, so Forge only alerts once no run has pinged for a whole day (plus the grace period).
     *
     * Step and range expressions are not expanded and stay CUSTOM, so the same schedule can map
     * differently depending on how it is written: "0 0,6,12,18 * * *" is DAILY, while
     * everySixHours() (a "/6" step on the hour field) and "0 8-17 * * *" are CUSTOM.
     */
    public static function fromCronExpression(string $cronExpression): self
    {
        // Fixed minute (0-59), one hour or a comma-separated list of hours (0-23), every day
        if (preg_match('/^[0-5]?\d (?:[01]?\d|2[0-3])(?:,(?:[01]?\d|2[0-3]))* \* \* \*$/', $cronExpression) === 1) {
            return self::DAILY;
        }

        return match ($cronExpression) {
            '* * * * *' => self::EVERY_MINUTE,
            '*/5 * * * *' => self::EVERY_5_MINUTES,
            '*/10 * * * *' => self::EVERY_10_MINUTES,
            '*/30 * * * *' => self::EVERY_30_MINUTES,
            '0 * * * *' => self::EVERY_HOUR,
            '0 0 * * 0' => self::WEEKLY,
            '0 0 1 * *' => self::MONTHLY,
            default => self::CUSTOM,
        };
    }
}
