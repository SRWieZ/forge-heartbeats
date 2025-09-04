<?php

namespace SRWieZ\ForgeHeartbeats\DTOs;

use Illuminate\Console\Scheduling\Event;

class ScheduledTask
{
    public function __construct(
        public readonly string $name,
        public readonly string $cronExpression,
        public readonly ?string $timezone,
        public readonly ?string $heartbeatName = null,
        public readonly ?int $graceTimeInMinutes = null,
        public readonly bool $skipMonitoring = false,
    ) {}

    public static function fromSchedulerEvent(Event $event, array $metadata = []): self
    {
        $command = $event->command;

        // Extract command name from full command
        $name = self::extractCommandName($command);

        return new self(
            name: $name,
            cronExpression: $event->expression,
            timezone: $event->timezone,
            heartbeatName: $metadata['heartbeat_name'] ?? null,
            graceTimeInMinutes: $metadata['grace_time'] ?? null,
            skipMonitoring: $metadata['skip_monitoring'] ?? false,
        );
    }

    private static function extractCommandName(string $command): string
    {
        // Handle different command formats:
        // 1. '/path/to/php' 'artisan' command
        // 2. php artisan command
        // 3. artisan command
        // 4. just command

        // Remove quotes and normalize spaces
        $cleaned = preg_replace("/'/", '', $command);

        // Try to find 'artisan' and extract everything after it
        if (preg_match('/\bartisan\s+(.+)/', $cleaned, $matches)) {
            $afterArtisan = trim($matches[1]);
            // Extract just the command name (first word after artisan)
            $parts = explode(' ', $afterArtisan);

            return $parts[0] ?? $afterArtisan;
        }

        // If no artisan found, try to extract command from the full string
        $parts = explode(' ', trim($cleaned));

        // Return the last part (likely the command)
        return end($parts);
    }

    public function getDisplayName(): string
    {
        return $this->heartbeatName ?? $this->name;
    }

    public function shouldBeMonitored(): bool
    {
        return ! $this->skipMonitoring;
    }
}
