<?php

return [
    /*
     * Your Laravel Forge API token, with at least the server:view and site:manage-heartbeats scopes.
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
     * Whether a task that fails (throws or exits with a non-zero code) still pings its heartbeat.
     * Set to false so Forge reports failing tasks as missing instead of healthy.
     */
    'ping_on_failure' => env('FORGE_HEARTBEAT_PING_ON_FAILURE', true),

    /*
     * Queue configuration for heartbeat pings.
     * Pings are queued to avoid blocking scheduled task execution.
     */
    'queue' => [
        'connection' => env('FORGE_HEARTBEAT_QUEUE_CONNECTION'),
        'name' => env('FORGE_HEARTBEAT_QUEUE'),
        'max_attempts' => 3,
        // Seconds to wait before retrying a failed ping.
        'backoff' => (int) env('FORGE_HEARTBEAT_QUEUE_BACKOFF', 60),
    ],

    /*
     * Default values for heartbeats when not specified on individual tasks.
     */
    'defaults' => [
        // Minutes; Forge accepts 1, 2, 5, 10, 30 or 60.
        'grace_period' => (int) env('FORGE_HEARTBEAT_GRACE_PERIOD', 5),
    ],

    /*
     * Laravel Horizon integration settings.
     */
    'horizon' => [
        'silence_ping_jobs' => true,
    ],

    /*
     * Cache for the list of heartbeats, so tasks find their ping URL without calling the Forge API.
     * It is kept until forge-heartbeats:sync or forge-heartbeats:list refreshes it.
     * Leave the store empty to use your default cache store.
     */
    'cache' => [
        'store' => env('FORGE_HEARTBEAT_CACHE_STORE'),
        'prefix' => 'forge_heartbeats',
    ],

    /*
     * Forge API configuration.
     */
    'api' => [
        // Change it to go through a proxy, for example. A trailing slash is fine.
        'base_url' => env('FORGE_HEARTBEAT_BASE_URL', 'https://forge.laravel.com/api'),
        'timeout' => 30, // seconds
    ],
];
