# Laravel Forge Heartbeats

[![Latest Version on Packagist](https://img.shields.io/packagist/v/srwiez/forge-heartbeats.svg?style=flat-square)](https://packagist.org/packages/srwiez/forge-heartbeats)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/srwiez/forge-heartbeats/tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/srwiez/forge-heartbeats/actions?query=workflow%3Atests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/srwiez/forge-heartbeats/static-analysis.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/srwiez/forge-heartbeats/actions?query=workflow%3A"static-analysis"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/srwiez/forge-heartbeats.svg?style=flat-square)](https://packagist.org/packages/srwiez/forge-heartbeats)

Monitor your Laravel scheduled tasks with **Laravel Forge Heartbeats**. This package automatically syncs your Laravel scheduler with Forge heartbeats and pings them when tasks run, providing you with reliable monitoring without any database overhead.

## Key Features

- =� **Zero Database Required** - Everything managed through Forge API
- <� **Automatic Sync** - Keep heartbeats in sync with your schedule
- = **Automatic Pinging** - Tasks ping heartbeats when they run
- =� **Beautiful CLI** - Rich terminal interface with status colors
- � **Queue Support** - Non-blocking heartbeat pings via queues
- =� **Error Handling** - Graceful handling of API failures
- <� **Customizable** - Configure grace periods, naming, and more

## Installation

You can install the package via Composer:

```bash
composer require srwiez/forge-heartbeats
```

Publish the configuration file:

```bash
php artisan vendor:publish --tag="forge-heartbeats-config"
```

## Configuration

Add these environment variables to your `.env` file:

```env
FORGE_API_TOKEN=your_forge_api_token_here
FORGE_ORGANIZATION=your_organization_slug
FORGE_SERVER_ID=12345
FORGE_SITE_ID=67890

# Optional: Default grace period for heartbeats (default: 5 minutes)
FORGE_HEARTBEAT_GRACE_PERIOD=5
```

### Getting Your Credentials

1. **API Token**: Generate in your Forge dashboard under **API** settings
2. **Organization**: Found in your Forge URL (`https://forge.laravel.com/orgs/{organization}`)
3. **Server ID**: Found in the server settings or URL
4. **Site ID**: Found in the site settings or URL

## Usage

### 1. Sync Your Schedule

After configuring your environment variables, sync your scheduled tasks:

```bash
php artisan forge:heartbeats:sync
```

This command will:
-  Create heartbeats for new scheduled tasks
-  Update existing heartbeats if changed
- =� Remove heartbeats for deleted tasks (unless `--keep-old`)

### 2. Monitor Status

View the status of all your heartbeats:

```bash
php artisan forge:heartbeats:list
```

This shows a beautiful terminal interface with:
- =� **Monitored Tasks** - Tasks with matching heartbeats
- � **Unmonitored Tasks** - Tasks without heartbeats
- =� **Orphaned Heartbeats** - Heartbeats without matching tasks
- S **Unnamed Tasks** - Tasks that can't be monitored

### 3. Verify Configuration

Test your Forge connection:

```bash
php artisan forge:heartbeats:verify
```

### 4. Customize Your Tasks

Add heartbeat configuration to your scheduled tasks:

```php
// app/Console/Kernel.php

protected function schedule(Schedule $schedule)
{
    // Basic task - uses command name as heartbeat name
    $schedule->command('backup:run')->daily();
    
    // Custom heartbeat name
    $schedule->command('reports:generate')
        ->hourly()
        ->heartbeatName('hourly-reports');
    
    // Custom grace period (time allowed for task to complete)
    $schedule->command('sync:users')
        ->daily()
        ->heartbeatName('user-sync')
        ->graceTimeInMinutes(30);
    
    // Skip monitoring for specific tasks
    $schedule->command('cache:clear')
        ->everyMinute()
        ->doNotMonitorAtForge();
}
```

## How It Works

### Automatic Heartbeat Pinging

The package automatically pings Forge heartbeats when your scheduled tasks:

-  **Finish successfully** - Pings to mark task as healthy  
- L **Fail** - Pings to mark task as failed
- � **Are skipped** - No ping (configurable)

### Queue Integration

Heartbeat pings are sent via queued jobs to avoid blocking your scheduled tasks. The package:

- =� Dispatches ping jobs immediately after task events
- = Retries failed pings with exponential backoff
- = Auto-silences jobs in Laravel Horizon
- � Uses configurable queue connections and names

### Sync Process

When you run `forge:heartbeats:sync`:

1. = **Analyzes** your Laravel schedule
2. =� **Fetches** existing heartbeats from Forge
3. = **Matches** tasks to heartbeats by name
4. ( **Creates/Updates/Deletes** as needed

## Advanced Configuration

The configuration file provides many options:

```php
// config/forge-heartbeats.php

return [
    // Forge API credentials
    'api_token' => env('FORGE_API_TOKEN'),
    'organization' => env('FORGE_ORGANIZATION'),
    'server_id' => env('FORGE_SERVER_ID'),
    'site_id' => env('FORGE_SITE_ID'),

    // Queue settings for heartbeat pings
    'queue' => [
        'connection' => env('FORGE_HEARTBEAT_QUEUE_CONNECTION', 'default'),
        'name' => env('FORGE_HEARTBEAT_QUEUE', 'default'),
        'retry_after' => 60, // seconds
        'max_attempts' => 3,
    ],

    // Default values for new heartbeats
    'defaults' => [
        'grace_period' => env('FORGE_HEARTBEAT_GRACE_PERIOD', 5), // minutes
        'frequency' => '0 * * * *', // hourly
    ],

    // Cache API responses to reduce calls
    'cache' => [
        'ttl' => 300, // seconds (5 minutes)
        'prefix' => 'forge_heartbeats',
    ],
];
```

## Production Deployment

### Add to Deployment Script

Add the sync command to your deployment process:

```bash
# After migrations and other setup
php artisan forge:heartbeats:sync
```

### Keep Old Heartbeats

If you monitor non-Laravel cron jobs in Forge, use the `--keep-old` flag to avoid deleting them:

```bash
php artisan forge:heartbeats:sync --keep-old
```

### Queue Worker

Ensure you have queue workers running for heartbeat pings:

```bash
php artisan queue:work
```

## Testing

Run the package tests:

```bash
composer test
```

Run tests with coverage:

```bash
composer test-coverage
```

Check code style:

```bash
composer format
```

Run static analysis:

```bash
composer analyse
```

## Troubleshooting

### Common Issues

**L "API token is required"**
- Check your `FORGE_API_TOKEN` is set in `.env`
- Verify the token is valid in your Forge dashboard

**L "API endpoint not found"**
- Verify your organization, server, and site IDs
- Check the IDs match what's shown in Forge URLs

**L "No scheduled tasks found"**
- Ensure you have `artisan` commands in your schedule
- The package only monitors `artisan` commands, not closures or shell commands

**� Heartbeat pings not working**
- Check your queue workers are running
- Verify the ping URLs in Forge dashboard
- Check Laravel logs for ping job failures

### Debug Mode

Enable debug logging to troubleshoot issues:

```env
LOG_LEVEL=debug
```

This will log heartbeat ping attempts and API calls.

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](https://github.com/srwiez/.github/blob/main/CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [SRWieZ](https://github.com/srwiez)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.