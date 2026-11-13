# Upgrade Guide

## Upgrading from 1.x to 2.0

Most apps only need steps 1, 2 and 6. Read the rest if you use Saloon, call the package's classes directly, fake its HTTP calls in tests, or published its config file.

### 1. Requirements

- PHP 8.2+ and **Laravel 12 or 13** (Laravel 13 needs PHP 8.3+). Laravel 10 and 11 are no longer supported.
- The package now requires `laravel/framework` instead of individual `illuminate/*` packages, and requires Composer 2 (`composer-runtime-api: ^2.0`).

Upgrade your app to Laravel 12 or 13 first, then:

```bash
composer require srwiez/forge-heartbeats:^2.0
```

### 2. Check `graceTimeInMinutes()` values

Forge only accepts grace periods of **1, 2, 5, 10, 30 or 60 minutes**. `graceTimeInMinutes()` now throws an `InvalidArgumentException` for any other value. In 1.x, the value was accepted and `forge-heartbeats:sync` failed later with a 422 from Forge.

Laravel loads the schedule in `routes/console.php` for every artisan command. So an invalid value now makes artisan commands fail until you fix it, and that includes the commands in your deploy script. Fix these values before you deploy. `FORGE_HEARTBEAT_GRACE_PERIOD` must also be one of these values.

### 3. Saloon is gone

The package now calls the Forge API with Laravel's HTTP client (the `Http` facade).

- **Saloon is no longer installed.** If your app uses `saloonphp/saloon` or `saloonphp/laravel-plugin` directly, require them in your own `composer.json`.
- **Removed classes.** `SRWieZ\ForgeHeartbeats\Http\Integrations\Forge\ForgeHeartbeatsConnector` and every class under `SRWieZ\ForgeHeartbeats\Http\Integrations\Forge\Requests\Heartbeats\` are gone. If you type-hinted, resolved or rebound them, use `SRWieZ\ForgeHeartbeats\Http\Client\ForgeHeartbeatsClient` instead. It has `listHeartbeats()`, `getHeartbeat()`, `createHeartbeat()`, `updateHeartbeat()` and `deleteHeartbeat()`.
- **Exceptions.** API errors now throw `Illuminate\Http\Client\RequestException`, and network errors (DNS, timeouts) throw `Illuminate\Http\Client\ConnectionException`. In 1.x they threw Saloon's exceptions. Update any `catch` blocks around `HeartbeatManager` or the client.
- **Your tests.** Replace Saloon `MockClient` fakes for this package with `Http::fake()`. These calls now go through the `Http` facade, so:
  - A suite that calls `Http::preventStrayRequests()` fails on them unless you fake them. This covers running `schedule:run`, dispatching scheduler events, and running the ping job on a `sync` queue.
  - Your app-wide `Http::globalOptions()` and `Http::globalMiddleware()`, and Telescope's HTTP client watcher, now apply to them.

### 4. Published config file

If you never published `config/forge-heartbeats.php`, skip this step. Otherwise, compare yours with the [new config file](config/forge-heartbeats.php) or publish it again with `php artisan vendor:publish --tag="forge-heartbeats-config" --force`.

The package merges its config with yours one level deep. New top-level keys reach you automatically. Inside a section you published (`queue`, `defaults`, `api`, ...), your copy wins as a whole.

| Key | Change |
| - | - |
| `api.base_url` | **Now used.** It was ignored in 1.x, where the URL was hard-coded. Check that it points to Forge (`https://forge.laravel.com/api`; a trailing slash is fine). To set it from `.env` with `FORGE_HEARTBEAT_BASE_URL`, change it to `env('FORGE_HEARTBEAT_BASE_URL', 'https://forge.laravel.com/api')`. |
| `api.retry_attempts`, `api.retry_delay` | Removed. They never had any effect, so you can delete them. |
| `queue.retry_after` | Replaced by **`queue.backoff`** (`FORGE_HEARTBEAT_QUEUE_BACKOFF`, default 60). Failed pings now wait this many seconds before they retry; in 1.x the setting was ignored and retries ran immediately. An old config that only has `retry_after` keeps working and uses its value as the backoff. Renaming it to `'backoff' => (int) env('FORGE_HEARTBEAT_QUEUE_BACKOFF', 60)` is recommended. |
| `defaults.grace_period` | Now cast with `(int)`. Older configs work too: the package casts the value when it reads it. |
| `defaults.frequency` | Removed. It was never used. |
| `ping_on_failure` | New, default `true`. See step 5. |

If your code reads `PingHeartbeatJob::$retryAfter`, use `PingHeartbeatJob::$backoff`.

### 5. Behaviour changes to know about

- **Daily heartbeats switch from Custom to Daily.** A task that runs once a day at any minute (`dailyAt('8:30')`), or at an explicit list of hours (`twiceDaily()`, `0 8,20 * * *`), is now synced with Forge's Daily frequency instead of a Custom cron. Your first `forge-heartbeats:sync` after upgrading reports these heartbeats as updated, which is expected. A task that runs several times a day this way is monitored with a 24-hour window. Step and range expressions such as `everySixHours()` stay Custom.
- **Failed tasks still ping by default.** The new `ping_on_failure` setting (`FORGE_HEARTBEAT_PING_ON_FAILURE`) defaults to `true`, the 1.x behaviour. Set it to `false` if you want Forge to report a task that fails (throws or exits with a non-zero code) as missing.
- **Fixed: closures and jobs no longer break `schedule:run`.** In 1.x, a finished `Schedule::call()` or `Schedule::job()` task made `schedule:run` throw a `TypeError` and skip the tasks after it. They are now ignored, as `forge-heartbeats:sync` already did.
- **Fixed: a grace period from `.env` no longer forces updates.** With `FORGE_HEARTBEAT_GRACE_PERIOD` set, 1.x updated every heartbeat on every sync and `forge-heartbeats:list` showed them as "Out of Sync".
- **User-Agent.** API requests now send `ForgeHeartbeats/v2.0.0` instead of `ForgeHeartbeats/2.0.0.0`.

### 6. Sync once after deploying

```bash
php artisan forge-heartbeats:sync
```

This switches your daily heartbeats to the Daily frequency and refreshes the cached heartbeat list.
