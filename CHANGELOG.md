# Changelog

All notable changes to `srwiez/forge-heartbeats` will be documented in this file.

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
