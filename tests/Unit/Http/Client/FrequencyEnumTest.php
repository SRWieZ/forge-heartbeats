<?php

use SRWieZ\ForgeHeartbeats\Http\Client\FrequencyEnum;

it('maps standard cron patterns', function (string $cron, FrequencyEnum $expected) {
    expect(FrequencyEnum::fromCronExpression($cron))->toBe($expected);
})->with([
    ['* * * * *', FrequencyEnum::EVERY_MINUTE],
    ['*/5 * * * *', FrequencyEnum::EVERY_5_MINUTES],
    ['*/10 * * * *', FrequencyEnum::EVERY_10_MINUTES],
    ['*/30 * * * *', FrequencyEnum::EVERY_30_MINUTES],
    ['0 * * * *', FrequencyEnum::EVERY_HOUR],
    ['0 0 * * *', FrequencyEnum::DAILY],
    ['0 0 * * 0', FrequencyEnum::WEEKLY],
    ['0 0 1 * *', FrequencyEnum::MONTHLY],
]);

it('maps once-per-day and multi-hour daily crons to DAILY', function (string $cron) {
    expect(FrequencyEnum::fromCronExpression($cron))->toBe(FrequencyEnum::DAILY);
})->with([
    '0 0 * * *',
    '30 8 * * *',
    '8 12 * * *',
    '59 23 * * *',
    '0 12,18 * * *',
    '0 8,10,12,20 * * *',
    // An hour list is DAILY even when it spreads runs over the day; the step form '0 */6 * * *' stays CUSTOM
    '0 0,6,12,18 * * *',
]);

it('does not map other crons to DAILY', function (string $cron) {
    expect(FrequencyEnum::fromCronExpression($cron))->not->toBe(FrequencyEnum::DAILY);
})->with([
    '0 * * * *',
    '0 8 * * 1',
    '0 25 * * *',
    '60 8 * * *',
    '*/5 * * * *',
    '0 */6 * * *',
    '0 8-17 * * *',
    '0 8 1 * *',
]);

it('returns CUSTOM for non-standard crons', function (string $cron) {
    expect(FrequencyEnum::fromCronExpression($cron))->toBe(FrequencyEnum::CUSTOM);
})->with([
    '0 8 * * 1',
    '0 25 * * *',
    '60 8 * * *',
    '0 */6 * * *',
]);
