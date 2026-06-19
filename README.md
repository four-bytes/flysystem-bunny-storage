# flysystem-bunny-storage

Flysystem v3 adapter for [Bunny Storage](https://bunny.net/storage/).

## Installation

```bash
composer require four-bytes/flysystem-bunny-storage
```

## Usage

```php
use Four\Flysystem\BunnyStorage\Adapter\BunnyStorageAdapter;
use Four\Flysystem\BunnyStorage\Client\BunnySdkClient;
use Four\Flysystem\BunnyStorage\Config\BunnyStorageConfig;
use League\Flysystem\Filesystem;

$config = new BunnyStorageConfig(
    apiKey: 'your-storage-api-key',
    storageZone: 'my-zone',
    region: 'de',
    cdnHostname: 'https://my-zone.b-cdn.net',
);

$filesystem = new Filesystem(
    new BunnyStorageAdapter(new BunnySdkClient($config))
);

$filesystem->write('path/to/file.txt', 'Hello Bunny');
$content = $filesystem->read('path/to/file.txt');
```

### With retry decorator

```php
use Four\Flysystem\Retry\RetryAdapter;
use Four\Flysystem\Retry\RetryPolicy;

$adapter = new RetryAdapter(
    new BunnyStorageAdapter(new BunnySdkClient($config)),
    new RetryPolicy(maxAttempts: 3, baseDelayMs: 100),
);

$filesystem = new Filesystem($adapter);
```

### Public URL generation

```php
use Four\Flysystem\BunnyStorage\Url\PublicUrlGenerator;
use League\Flysystem\Filesystem;

$filesystem = new Filesystem(
    new BunnyStorageAdapter(new BunnySdkClient($config)),
    publicUrlGenerator: new PublicUrlGenerator($config),
);

$url = $filesystem->publicUrl('media/image.jpg');
// https://my-zone.b-cdn.net/media/image.jpg
```

## Configuration

| Parameter | Type | Default | Description |
|---|---|---|---|
| `apiKey` | string | — | Bunny Storage API key |
| `storageZone` | string | — | Storage zone name |
| `region` | string | `de` | Storage region (`de`, `uk`, `ny`, `la`, `sg`, `syd`) |
| `cdnHostname` | ?string | `null` | CDN pull zone hostname for public URL generation |

## Requirements

- PHP 8.2+
- `league/flysystem` ^3.0
- `bunnycdn/storage` ^2.0

## License

Apache License 2.0 — see [LICENSE](LICENSE).
