# Changelog

All notable changes to `srwiez/forge-heartbeats` will be documented in this file.

## v2.0.0 - 2026-11-13

Version 2.0 moves the package to Laravel's HTTP client, drops Laravel 10 and 11, and fixes several long-standing bugs. **Read the [upgrade guide](https://github.com/SRWieZ/forge-heartbeats/blob/main/UPGRADE.md) before upgrading.**

### ⚠️ Breaking changes

- **Requires Laravel 12 or 13** and PHP 8.2+ (Laravel 13 needs PHP 8.3+). Laravel 10 and 11 are no longer supported (#11). The package now requires `laravel/framework` and `composer-runtime-api: ^2.0` instead of individual `illuminate/*` packages (#19).
- **Saloon removed.** The Forge API is called through Laravel's HTTP client by the new `SRWieZ\ForgeHeartbeats\Http\Client\ForgeHeartbeatsClient` (#12). `ForgeHeartbeatsConnector` and every `Http\Integrations\Forge\Requests\Heartbeats\*` class are gone, and Saloon is no longer installed.
- **Exceptions.** API failures throw `Illuminate\Http\Client\RequestException`, and network errors throw `Illuminate\Http\Client\ConnectionException`, instead of Saloon exceptions (#12). Tests that faked this package with Saloon's `MockClient` must use `Http::fake()`.
- **Stricter grace periods.** `graceTimeInMinutes()` throws an `InvalidArgumentException` unless the value is one Forge accepts: 1, 2, 5, 10, 30 or 60 minutes (#17). The schedule in `routes/console.php` loads for every artisan command, so **an invalid value now makes every artisan command fail**, including the ones in your deploy script, until you fix it.
- **Daily schedules sync as Daily.** A task that runs once a day at any minute (`dailyAt('8:30')`), or at an explicit list of hours (`twiceDaily()`), now uses Forge's Daily frequency instead of a Custom cron (#6, #18). **The first `forge-heartbeats:sync` after upgrading switches these heartbeats from Custom to Daily** and reports them as updated, which is expected.
- **Config changes.**
  - `queue.retry_after` is replaced by `queue.backoff` (#15). Old configs still work through a fallback.
  - `api.retry_attempts`, `api.retry_delay` (#12) and `defaults.frequency` were removed. None of them did anything.
  - `PingHeartbeatJob::$retryAfter` is now `$backoff`.
- **Nullable fields.** `Heartbeat::$status` and `Heartbeat::$pingUrl` can now be `null`, as the Forge API allows (#21).

### ✨ New

- **`api.base_url` is now used**, and can be set with `FORGE_HEARTBEAT_BASE_URL`, for example to go through a proxy (#12). In 1.x it was ignored.
- **`ping_on_failure`** (`FORGE_HEARTBEAT_PING_ON_FAILURE`, default `true`, the same as 1.x) controls whether a task that fails still pings its heartbeat (#16). Set it to `false` to have Forge report failing tasks as missing.
- **`queue.backoff`** (`FORGE_HEARTBEAT_QUEUE_BACKOFF`, default 60 seconds): failed pings now wait before they retry. In 1.x they retried immediately (#15).

### 🐛 Fixes

- Scheduled closures and jobs (`Schedule::call()`, `Schedule::job()`) no longer crash `schedule:run` and skip the tasks after them (#13).
- Setting `FORGE_HEARTBEAT_GRACE_PERIOD` no longer makes every sync update every heartbeat, or `list` show them as "Out of Sync" (#14).
- Sites with more than 30 heartbeats: the package now reads every page of Forge's cursor-paginated list. Before, sync created duplicates and missed orphans past the first page (#20).
- A heartbeat with a null `status` or `ping_url` no longer throws a `TypeError`. A task without a ping URL is skipped with a logged warning (#21).
- `forge-heartbeats:verify` reports an unreachable Forge API with a clear message and exit code 1, instead of a stack trace (#22).

### 📚 Docs and maintenance

- The README was rewritten for 2.0. The beta note is gone. It now has:
  - the full list of env variables
  - which tasks are monitored
  - the allowed grace periods
  - how the heartbeat cache works
- The config file has new comments.
- New [UPGRADE.md](https://github.com/SRWieZ/forge-heartbeats/blob/main/UPGRADE.md), and the missing v1.4.0 CHANGELOG entry was added.
- `FrequencyEnum` cleanup and docs (#18).
- CI and packaging (#19):
  - testbench `^10.8`
  - no coverage driver
  - cache keys on `composer.json`
  - tests also run on `develop`
  - leaner dist archive
  - User-Agent now `ForgeHeartbeats/v2.0.0`
- GitHub Actions bumps (#7, #8, #10), PHPStan result cache in CI, a Dependabot cooldown, and a Laravel compatibility badge.
- #9 allowed `saloonphp/laravel-plugin ^5.0` for Guzzle 8. #12 then removed Saloon entirely, and Guzzle 8 is supported through Laravel 13's HTTP client.

### New Contributors

- @ziming made their first contributions in #6 and #9. Thank you!

**Full Changelog**: https://github.com/SRWieZ/forge-heartbeats/compare/v1.4.0...v2.0.0

## v1.4.0 - 2026-05-19

### What's Changed

* Add Laravel 13 compatibility by @stephanus-tantiono in https://github.com/SRWieZ/forge-heartbeats/pull/5

### New Contributors

* @stephanus-tantiono made their first contribution in https://github.com/SRWieZ/forge-heartbeats/pull/5

**Full Changelog**: https://github.com/SRWieZ/forge-heartbeats/compare/v1.3.0...v1.4.0

## v1.3.0 - 2026-05-19

- Restore Laravel 10 support by widening saloonphp constraints to `^3.0 || ^4.0` (#3)
- CI / supply-chain hardening in response to Composer CVE-2026-45793 (#3)

**Full Changelog**: https://github.com/SRWieZ/forge-heartbeats/compare/v1.2.1...v1.3.0

## v1.2.1 - 2026-03-30

fix: remove saloon v3 entirely

**Full Changelog**: https://github.com/SRWieZ/forge-heartbeats/compare/v1.2.0...v1.2.1

## v1.2.0 - 2026-03-30

### Release Notes

* chore: drop support for PHP 8.1 in [`3090392`](https://github.com/SRWieZ/forge-heartbeats/commit/3090392c2e295eccb65fcf00193fe8a33b21a928)
* fix: update saloonphp to 4.0 in [`f6d1ebc`](https://github.com/SRWieZ/forge-heartbeats/commit/f6d1ebc91e59e68d982b889cbbb0d28a428c8643)

**Full Changelog**: https://github.com/SRWieZ/forge-heartbeats/compare/v1.1.0..v1.2.0

## v1.1.0 - 2025-11-27

### What's Changed

* feat: add support for PHP 8.5 by @SRWieZ in https://github.com/SRWieZ/forge-heartbeats/pull/2

**Full Changelog**: https://github.com/SRWieZ/forge-heartbeats/compare/v1.0.0...v1.1.0

## v1.0.0 - 2025-09-11

**Laravel Forge Heartbeats v1.0.0**

Monitor Laravel scheduled commands with Forge Heartbeats API

Automatic synchronization between Laravel's scheduler and Forge heartbeats

🛠️ Commands

- `forge-heartbeats:sync` → Sync scheduled tasks with Forge
- `forge-heartbeats:verify` → Verify API connection
- `forge-heartbeats:list` → List all heartbeats status

📦 Requirements

- PHP 8.1+
- Laravel 10.x, 11.x, or 12.x
- Forge API v2 (beta)

⚠️ Note

Requires Forge v2 API (currently in beta behind feature flag)

**Full Changelog**: https://github.com/SRWieZ/forge-heartbeats/commits/v1.0.0
