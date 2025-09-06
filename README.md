# Laravel Forge Heartbeats

[![Latest Version on Packagist](https://img.shields.io/packagist/v/srwiez/forge-heartbeats.svg?style=flat-square)](https://packagist.org/packages/srwiez/forge-heartbeats)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/srwiez/forge-heartbeats/tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/srwiez/forge-heartbeats/actions?query=workflow%3Atests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/srwiez/forge-heartbeats/static-analysis.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/srwiez/forge-heartbeats/actions?query=workflow%3A"static-analysis"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/srwiez/forge-heartbeats.svg?style=flat-square)](https://packagist.org/packages/srwiez/forge-heartbeats)

Monitor your Laravel scheduled tasks with **Laravel Forge Heartbeats**. 

This package automatically syncs your Laravel scheduler with Forge heartbeats and pings them when tasks run.


- **No Database Required** - Everything managed through Forge API
- **Automatic Sync** - Keep heartbeats in sync with your schedule
- **Automatic Pinging** - Tasks ping heartbeats when they run
- **Queue Support** - Non-blocking heartbeat pings via queues

> **Note**: this package is not ready yet. Forge v2 API is still in beta and behind a feature flag.

## 🚀 Installation

You can install the package via Composer:

```bash
composer require srwiez/forge-heartbeats
```


## 📚 Usage

### 1. Get your credentials

1. **API Token**: Generate in your Forge dashboard (https://forge.laravel.com/profile/api). You only at least `site:manage-heartbeats` and `server:view` permission for this package.
2. **Organization**: Look for the slug in the URL when viewing your organization (e.g., `my-org` in `https://forge.laravel.com/your-organization/`)
3. **Server ID**: Found in the server homepage
4. **Site ID**: Found in the site homepage

### 2. Set environment variables

Add these environment variables to your `.env` file:

```env
FORGE_API_TOKEN=your_forge_api_token_here
FORGE_ORGANIZATION=your_organization_slug
FORGE_SERVER_ID=12345
FORGE_SITE_ID=67890

# Optional: Default grace period for heartbeats (default: 5 minutes)
FORGE_HEARTBEAT_GRACE_PERIOD=5

# Optional: Custom cache store for heartbeat data (default: uses Laravel's default cache)
FORGE_HEARTBEAT_CACHE_STORE=redis
```

For more configuration options, publish the config file:

```bash
php artisan vendor:publish --tag="forge-heartbeats-config"
```

### 3. Verify your configuration

Test your Forge connection:

```bash
php artisan forge-heartbeats:verify
```

Then view the status of all your heartbeats:

```bash
php artisan forge-heartbeats:list
```

### 4. Sync Your Schedule

This should run after every deployment to keep heartbeats up to date.

```bash
php artisan forge-heartbeats:sync
```

This command will:
- Create heartbeats for new scheduled tasks
- Update existing heartbeats if changed
- Remove heartbeats for deleted tasks (unless `--keep-old`)

### 5. Customize Your Tasks

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

## Advanced usage

### Keep Old Heartbeats

If you monitor non-Laravel cron jobs in Forge, use the `--keep-old` flag to avoid deleting them:

```bash
php artisan forge-heartbeats:sync --keep-old
```

## 👥 Credits

- [SRWieZ](https://github.com/srwiez)
- [All Contributors](../../contributors)

## 📝 License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.