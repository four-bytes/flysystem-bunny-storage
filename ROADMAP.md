# Roadmap: four-bytes/flysystem-bunny-storage

Generic Flysystem v3 adapter for the Bunny Storage API. No Shopware dependency — usable in any PHP 8.2+ / Symfony project.

## Phase 0 — Namespace & scaffold verification ✅

- [x] Confirm PSR-4 root `Four\Flysystem\BunnyStorage\` matches `composer.json` and all `src/` files
- [x] Verify `PublicUrlGenerator` implements the correct Flysystem v3 interface (`League\Flysystem\UrlGeneration\PublicUrlGenerator`)
- [x] Confirm `bunnycdn/storage` is the correct Packagist package name and pin a stable version
- [x] Run `composer validate` and `composer install`

## Phase 1 — BunnySdkClient implementation ✅

`src/Client/BunnySdkClient.php` — all stubs replaced with working implementations:

- [x] `upload(string $path, mixed $content)` — handles both string and stream resource
- [x] `download(string $path): string`
- [x] `downloadStream(string $path): resource` — downloads to `php://memory` stream
- [x] `delete(string $path)` — `FileNotFoundException` mapped to silent no-op (idempotent)
- [x] `exists(string $path): bool` — delegates to SDK `exists()` (uses DESCRIBE verb, file-only)
- [x] `directoryExists(string $path): bool` — `listFiles(path/)` + non-empty check
- [x] `list(string $path): array` — maps SDK response to `{name, is_directory, size, last_modified}`; strips storage-zone prefix from `Path`
- [x] `move(string $from, string $to)` — copy then delete (Bunny has no native rename)
- [x] `copy(string $from, string $to)` — `getContents` + `putContents`
- [x] `AuthenticationException` / `FileNotFoundException` re-thrown as permanent `UnableToXxx` exceptions
- [x] All other `Bunny\Storage\Exception` mapped to `TransientBunnyException`

## Phase 2 — BunnyStorageAdapter full implementation ✅

`src/Adapter/BunnyStorageAdapter.php`:

- [x] `mimeType()` — derived from file extension via `ExtensionMimeTypeDetector` (Bunny list has no MIME field)
- [x] `lastModified()` — fetched from `client->list(dirname($path))` entry lookup
- [x] `fileSize()` — fetched from `client->list(dirname($path))` entry lookup
- [x] `visibility()` — returns empty `FileAttributes` (Bunny has no per-object ACL)
- [x] `setVisibility()` — no-op (not supported)
- [x] `directoryExists()` — delegates to `client->directoryExists()`
- [x] `listContents()` recursive mode — `yield from` subdirectory listing
- [x] `deleteDirectory()` — recursive object enumeration + delete loop (Bunny has no directory delete API)

## Phase 3 — Exception translation ✅

- [x] Transient HTTP codes (5xx, 429, timeout) → `TransientBunnyException extends \RuntimeException`
- [x] `AuthenticationException` (401/403) → permanent `UnableToXxx`
- [x] `FileNotFoundException` (404) → permanent `UnableToXxx` or silent no-op on delete
- [x] `RetryClassifier` default changed to `[]` (opt-in) to avoid retrying Flysystem's own `UnableToXxx` exceptions

## Phase 4 — URL generation ✅

- [x] `PublicUrlGenerator::publicUrl()` — builds `https://{cdnHostname}/{path}`
- [x] Trailing slash normalisation on `cdnHostname`
- [ ] Optional path prefix support in `BunnyStorageConfig`

## Phase 5 — Tests ✅

- [x] `BunnyStorageAdapterTest` — 27 unit tests with mocked `BunnyClientInterface`
- [x] `PublicUrlGeneratorTest` — 4 tests
- [x] `BunnyStorageConfigTest` — 3 tests
- [x] `BunnyStorageAdapterIntegrationTest` — 11 real-life tests (skipped without `BUNNY_API_KEY` + `BUNNY_STORAGE_ZONE` env vars); run with `--group integration`
- [ ] Contract test using Flysystem's `FilesystemAdapterTestCase`

## Phase 6 — Hardening & CI

- [ ] PHPStan level 8
- [ ] GitHub Actions: PHP 8.2 + 8.3, PHPUnit, PHPStan
- [ ] `composer.json` keywords, homepage, minimum-stability
- [ ] Tag v0.1.0 and submit to Packagist

## Known limitations

- `mimeType()` is extension-based only — Bunny Storage does not expose `Content-Type` in list responses
- `visibility()` always returns no value — Bunny Storage has no per-object ACL
- `directoryExists()` lists the directory to check non-empty; empty directories are not representable in Bunny Storage
- All file I/O (`upload`, `download`, `copy`) buffers the entire file as a PHP string — Bunny SDK v3 uses string-based `putContents`/`getContents`. Large files will exhaust memory. `uploadAsync` writes content to a temp file before calling the SDK, but still calls `stream_get_contents()` internally for resource inputs, so it does not reduce peak memory usage — it only provides a non-blocking HTTP upload via Guzzle promises.

## Acceptance criteria

- Implements full `FilesystemAdapter` interface
- No Shopware dependency
- All transient errors are classified so a `RetryAdapter` wrapper can retry them
- PHPStan level 8 clean
- Ships with unit tests + optional integration tests
