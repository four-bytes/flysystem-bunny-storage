# Roadmap: four-bytes/flysystem-bunny-storage

Generic Flysystem v3 adapter for the Bunny Storage API. No Shopware dependency — usable in any PHP 8.2+ / Symfony project.

## Phase 0 — Namespace & scaffold verification

- [ ] Confirm PSR-4 root `Four\Flysystem\BunnyStorage\` matches `composer.json` and all `src/` files
- [ ] Verify `PublicUrlGenerator` implements the correct Flysystem v3 interface (`League\Flysystem\UrlGeneration\PublicUrlGenerator`)
- [ ] Confirm `bunnycdn/storage` is the correct Packagist package name and pin a stable version
- [ ] Run `composer validate` and `composer install`

## Phase 1 — BunnySdkClient implementation

`src/Client/BunnySdkClient.php` — replace all `throw new \LogicException('Not implemented')` stubs:

- [ ] `upload(string $path, mixed $content)` — handle both string and stream resource
- [ ] `download(string $path): string`
- [ ] `downloadStream(string $path): resource`
- [ ] `delete(string $path)` — map 404 to silent no-op (idempotent delete)
- [ ] `exists(string $path): bool` — HEAD request or list + filter
- [ ] `list(string $path): array` — map SDK response to `{name, is_directory, size, last_modified}`
- [ ] `move(string $from, string $to)` — copy then delete (Bunny has no native rename)
- [ ] `copy(string $from, string $to)`
- [ ] Map all SDK exceptions to `TransientBunnyException` (5xx, timeout, connection reset, 429) or rethrow as permanent `UnableToXxx` Flysystem exceptions

## Phase 2 — BunnyStorageAdapter full implementation

`src/Adapter/BunnyStorageAdapter.php` — complete all metadata methods:

- [ ] `mimeType()` — derive from file extension or SDK response header
- [ ] `lastModified()` — parse from SDK list response
- [ ] `fileSize()` — parse from SDK list response
- [ ] `visibility()` — Bunny has no per-object ACL; return `public` as constant
- [ ] `setVisibility()` — no-op with a logged warning
- [ ] `directoryExists()` — list with trailing slash, check non-empty result
- [ ] `deleteDirectory()` — list recursively, delete all objects, Bunny has no directory delete
- [ ] `listContents()` recursive mode — yield from subdirectory listing

## Phase 3 — Exception translation

- [ ] Define which HTTP status codes are transient (429, 500, 502, 503, 504) vs permanent (400, 401, 403, 404)
- [ ] Map transient SDK errors to `TransientBunnyException` so `RetryAdapter` can classify them
- [ ] Map permanent errors to Flysystem `UnableToReadFile`, `UnableToWriteFile`, etc.

## Phase 4 — URL generation

- [ ] `PublicUrlGenerator::publicUrl()` — build `https://{cdnHostname}/{path}`
- [ ] Handle trailing slash normalisation on `cdnHostname`
- [ ] Add optional path prefix support to `BunnyStorageConfig`

## Phase 5 — Tests

- [ ] `BunnyStorageAdapterTest` — unit tests with mocked `BunnyClientInterface`
  - write / read / delete / move / copy round-trips
  - 404 on delete is silent
  - transient error propagates as `TransientBunnyException`
- [ ] Contract test using Flysystem's `FilesystemAdapterTestCase` with a mock client
- [ ] `PublicUrlGeneratorTest`

## Phase 6 — Hardening & CI

- [ ] PHPStan level 8
- [ ] GitHub Actions: PHP 8.2 + 8.3, PHPUnit, PHPStan
- [ ] `composer.json` keywords, homepage, minimum-stability

## Acceptance criteria

- Implements full `FilesystemAdapter` interface
- No Shopware dependency
- All transient errors are classified so a `RetryAdapter` wrapper can retry them
- PHPStan level 8 clean
- Ships with unit + contract tests
