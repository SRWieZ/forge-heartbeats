# Laravel Forge Heartbeats

[![Latest Version on Packagist](https://img.shields.io/packagist/v/srwiez/forge-heartbeats.svg?style=flat-square)](https://packagist.org/packages/srwiez/forge-heartbeats)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/srwiez/forge-heartbeats/tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/srwiez/forge-heartbeats/actions?query=workflow%3Atests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/srwiez/forge-heartbeats/static-analysis.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/srwiez/forge-heartbeats/actions?query=workflow%3A"static-analysis"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/srwiez/forge-heartbeats.svg?style=flat-square)](https://packagist.org/packages/srwiez/forge-heartbeats)
[![Laravel Version Compatibility](https://badge.laravel.cloud/badge/srwiez/forge-heartbeats?style=flat)](https://packagist.org/packages/srwiez/forge-heartbeats)

Monitor your Laravel scheduled commands with **Laravel Forge Heartbeats**. 

This package automatically syncs your Laravel scheduled commands with Forge heartbeats and pings them when tasks run.


- **No Database Required** - Everything managed through Forge API
- **Automatic Sync** - Keep heartbeats in sync with your schedule
- **Automatic Pinging** - Tasks ping heartbeats when they run
- **Queue Support** - Non-blocking heartbeat pings via queues

## 🚀 Installation

Requires PHP 8.2+ and Laravel 12 or 13 (Laravel 13 requires PHP 8.3+).

You can install the package via Composer:

```bash
composer require srwiez/forge-heartbeats
```


## 📚 Usage

### 1. Get your credentials

1. **API Token**: Generate in your Forge dashboard (https://forge.laravel.com/profile/api). You need at least `site:manage-heartbeats` and `server:view` permission for this package.
2. **Organization**: Look for the slug in the URL when viewing your organization (e.g., `my-org` in `https://forge.laravel.com/my-org/`)
3. **Server ID**: Found in the server homepage
4. **Site ID**: Found in the site homepage

### 2. Set environment variables

Add these environment variables to your `.env` file:

```env
FORGE_API_TOKEN=your_forge_api_token_here
FORGE_ORGANIZATION=your_organization_slug
FORGE_SERVER_ID=12345
FORGE_SITE_ID=67890
```

All other settings are optional. Uncomment the ones you want to change:

```env
# Default grace period in minutes: 1, 2, 5, 10, 30 or 60 (default: 5)
# FORGE_HEARTBEAT_GRACE_PERIOD=5

# Whether failing tasks still ping their heartbeat (default: true), see "Failed tasks" below
# FORGE_HEARTBEAT_PING_ON_FAILURE=true

# Queue connection and queue for ping jobs (default: your app's default connection and queue)
# FORGE_HEARTBEAT_QUEUE_CONNECTION=redis
# FORGE_HEARTBEAT_QUEUE=default

# Seconds to wait before retrying a failed ping (default: 60)
# FORGE_HEARTBEAT_QUEUE_BACKOFF=60

# Cache store for the heartbeat list (default: your app's default cache store)
# FORGE_HEARTBEAT_CACHE_STORE=redis

# Forge API base URL, for example to go through a proxy (default: https://forge.laravel.com/api)
# FORGE_HEARTBEAT_BASE_URL=https://forge.laravel.com/api
```

For the remaining options (`queue.max_attempts`, `api.timeout`, the Horizon and cache prefix settings), publish the config file:

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

Only command tasks are monitored: `Schedule::command()` (artisan commands) and `Schedule::exec()` (shell commands). Closures (`Schedule::call()`) and queued jobs (`Schedule::job()`) are skipped. A heartbeat is named after its artisan command, or the last word of a shell command, unless you set `heartbeatName()`.

#### Cache

To find a heartbeat's ping URL without calling the Forge API every time a task runs, the package caches the list of heartbeats forever, in the store set by `FORGE_HEARTBEAT_CACHE_STORE` (your default cache store if empty). `forge-heartbeats:sync` and `forge-heartbeats:list` refresh it. If you change heartbeats in the Forge dashboard, run one of them (or clear the cache) so tasks pick up the change.

### 5. Customize Your Tasks

Add heartbeat configuration to your scheduled tasks:

```php
// routes/console.php

use Illuminate\Support\Facades\Schedule;

// Basic task - uses command name as heartbeat name
Schedule::command('backup:run')->daily();

// Custom heartbeat name
Schedule::command('reports:generate')
    ->hourly()
    ->heartbeatName('hourly-reports');

// Custom grace period (time allowed for task to complete)
Schedule::command('sync:users')
    ->daily()
    ->heartbeatName('user-sync')
    ->graceTimeInMinutes(30);

// Skip monitoring for specific tasks
Schedule::command('cache:clear')
    ->everyMinute()
    ->doNotMonitorOnForge();
```

Forge only accepts grace periods of **1, 2, 5, 10, 30 or 60 minutes**. Any other value passed to `graceTimeInMinutes()` throws an `InvalidArgumentException`. The same values apply to `FORGE_HEARTBEAT_GRACE_PERIOD`.

### 6. Failed tasks

By default, a task pings its heartbeat even when it fails (throws an exception or exits with a non-zero code), so Forge only alerts you when a task doesn't run at all. To have Forge also report failing tasks as missing, stop pinging on failure:

```env
FORGE_HEARTBEAT_PING_ON_FAILURE=false
```

or set `ping_on_failure` to `false` in the published config.

## 👥 Credits

- [SRWieZ](https://github.com/srwiez)
- [All Contributors](../../contributors)

## 📝 License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.