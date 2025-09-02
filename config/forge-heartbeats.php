<?php

return [
    /*
     * Your Laravel Forge API token.
     * You can generate an API token in your Forge dashboard under API settings.
     */
    'api_token' => env('FORGE_API_TOKEN'),

    /*
     * Your Forge organization slug.
     */
    'organization' => env('FORGE_ORGANIZATION'),

    /*
     * The server ID where your heartbeats will be created.
     */
    'server_id' => env('FORGE_SERVER_ID'),

    /*
     * The site ID where your heartbeats will be created.
     */
    'site_id' => env('FORGE_SITE_ID'),

    /*
     * Queue configuration for heartbeat pings.
     * Pings are queued to avoid blocking scheduled task execution.
     */
    'queue' => [
        'connection' => env('FORGE_HEARTBEAT_QUEUE_CONNECTION', 'default'),
        'name' => env('FORGE_HEARTBEAT_QUEUE', 'default'),
        'retry_after' => 60, // seconds
        'max_attempts' => 3,
    ],

    /*
     * Default values for heartbeats when not specified on individual tasks.
     */
    'defaults' => [
        'grace_period' => env('FORGE_HEARTBEAT_GRACE_PERIOD', 5), // minutes
        'frequency' => '0 * * * *', // hourly
    ],

    /*
     * Laravel Horizon integration settings.
     */
    'horizon' => [
        'silence_ping_jobs' => true,
    ],

    /*
     * Cache settings for API responses.
     * This helps reduce the number of API calls to Forge.
     */
    'cache' => [
        'ttl' => 300, // seconds (5 minutes)
        'prefix' => 'forge_heartbeats',
    ],

    /*
     * Forge API configuration.
     */
    'api' => [
        'base_url' => 'https://forge.laravel.com/api',
        'timeout' => 30, // seconds
        'retry_attempts' => 3,
        'retry_delay' => 1000, // milliseconds
    ],
];