# History

## Unreleased

- Full `BunnySdkClient` implementation (upload, download, stream, delete, exists, directoryExists, list, copy, move)
- `BunnyStorageAdapter`: all `FilesystemAdapter` methods including `mimeType`, `lastModified`, `fileSize`, `deleteDirectory`
- Optional real-life integration tests (skipped without `BUNNY_API_KEY` + `BUNNY_STORAGE_ZONE`)
- `PublicUrlGenerator` for CDN pull zone URL generation
- Apache-2.0 license
