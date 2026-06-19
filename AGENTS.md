# flysystem-bunny-storage — AGENTS.md

## Project Overview

Generic Flysystem v3 adapter for the Bunny Storage API. Framework-agnostic — works in any PHP 8.2+ project (Symfony, Shopware, standalone). No Shopware dependency.

- **Package**: `four-bytes/flysystem-bunny-storage`
- **Namespace**: `Four\Flysystem\BunnyStorage\`
- **PHP**: 8.2+
- **Flysystem**: ^3.0
- **Bunny SDK**: `bunnycdn/storage` ^2.0

## Architecture

```
src/
├── Adapter/
│   └── BunnyStorageAdapter.php     # FilesystemAdapter implementation
├── Client/
│   ├── BunnyClientInterface.php    # Abstraction over the Bunny SDK
│   └── BunnySdkClient.php          # Wraps bunnycdn/storage — all TODOs live here
├── Config/
│   └── BunnyStorageConfig.php      # Readonly DTO: apiKey, storageZone, region, cdnHostname
├── Exception/
│   ├── TransientBunnyException.php # 429/5xx → classified as retryable
│   └── UnableToGenerateUrl.php     # Thrown when no CDN hostname is configured
└── Url/
    └── PublicUrlGenerator.php      # Implements Flysystem PublicUrlGenerator interface
```

## Key Design Decisions

- `BunnyClientInterface` sits between the adapter and the SDK so the SDK can be swapped or mocked in tests without touching adapter logic.
- Transient errors (429, 5xx, network) are thrown as `TransientBunnyException` so `four-bytes/flysystem-retry` can classify and retry them.
- Permanent errors (401, 403, 404) map directly to Flysystem `UnableToXxx` exceptions.
- Bunny has no per-object ACL — `visibility()` returns `public` constant, `setVisibility()` is a no-op.
- Directory deletion requires listing all objects under the prefix and deleting them individually.

## Development Commands

```bash
composer install
composer test          # runs PHPUnit
composer phpstan       # static analysis level 8
```

Add to `composer.json` scripts if not present:
```json
"scripts": {
    "test": "phpunit",
    "phpstan": "phpstan analyse src tests --level=8"
}
```

## Running Tests

```bash
vendor/bin/phpunit
vendor/bin/phpunit tests/Adapter/BunnyStorageAdapterTest.php
```

All tests use mocked `BunnyClientInterface` — no real Bunny API credentials needed.

## Bunny Storage API Notes

- Base URL: `https://{region}.storage.bunnycdn.com/{storageZone}/`
- Auth: `AccessKey` header
- No native rename — `move` = copy + delete
- No directory create — directories emerge from object paths
- `deleteDirectory` must list and delete each object individually
- HTTP 404 on delete should be treated as a silent no-op (idempotent)
- Retryable: 429, 500, 502, 503, 504, network errors
- Permanent: 400, 401, 403, 404 (except on delete)
