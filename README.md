# Laravel Forge Heartbeats

[![Latest Version on Packagist](https://img.shields.io/packagist/v/srwiez/forge-heartbeats.svg?style=flat-square)](https://packagist.org/packages/srwiez/forge-heartbeats)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/srwiez/forge-heartbeats/tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/srwiez/forge-heartbeats/actions?query=workflow%3Atests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/srwiez/forge-heartbeats/static-analysis.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/srwiez/forge-heartbeats/actions?query=workflow%3A"static-analysis"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/srwiez/forge-heartbeats.svg?style=flat-square)](https://packagist.org/packages/srwiez/forge-heartbeats)

Monitor your Laravel scheduled tasks with **Laravel Forge Heartbeats**. This package automatically syncs your Laravel scheduler with Forge heartbeats and pings them when tasks run, providing you with reliable monitoring without any database overhead.

## Key Features

- **Zero Database Required** - Everything managed through Forge API
- **Automatic Sync** - Keep heartbeats in sync with your schedule
- **Automatic Pinging** - Tasks ping heartbeats when they run
- **Queue Support** - Non-blocking heartbeat pings via queues

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
2. **Organization**: Found in your Forge URL (`https://forge.laravel.com/profile/api`)
3. **Server ID**: Found in the server homepage
4. **Site ID**: Found in the site homepage

## Usage

### 1. Sync Your Schedule

After configuring your environment variables, sync your scheduled tasks:

This should run after every deployment to keep heartbeats up to date.

```bash
php artisan forge-heartbeats:sync
```

This command will:
- Create heartbeats for new scheduled tasks
- Update existing heartbeats if changed
- Remove heartbeats for deleted tasks (unless `--keep-old`)

### 2. Monitor Status

View the status of all your heartbeats:

```bash
php artisan forge-heartbeats:list
```

### 3. Verify Configuration

Test your Forge connection:

```bash
php artisan forge-heartbeats:verify
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
        ->doNotMonitorOnForge();
}
```

## Production Deployment

### Add to Deployment Script

Add the sync command to your deployment process:

```bash
# After migrations and other setup
php artisan forge-heartbeats:sync
```

### Keep Old Heartbeats

If you monitor non-Laravel cron jobs in Forge, use the `--keep-old` flag to avoid deleting them:

```bash
php artisan forge-heartbeats:sync --keep-old
```

## Testing

Run all the quality assurance checks:

```bash
composer qa
```

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

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Credits

This package is heavily inspired by [spatie/laravel-schedule-monitor](https://github.com/spatie/laravel-schedule-monitor) so big thanks to them for the idea and implementation.

- [SRWieZ](https://github.com/srwiez)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.