<?php

declare(strict_types=1);

namespace Four\Flysystem\BunnyStorage\Adapter;

use Four\Flysystem\BunnyStorage\Client\BunnyClientInterface;
use League\Flysystem\Config;
use League\Flysystem\DirectoryAttributes;
use League\Flysystem\FileAttributes;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\StorageAttributes;
use League\MimeTypeDetection\ExtensionMimeTypeDetector;

final class BunnyStorageAdapter implements FilesystemAdapter
{
    private readonly ExtensionMimeTypeDetector $mimeDetector;

    public function __construct(private readonly BunnyClientInterface $client)
    {
        $this->mimeDetector = new ExtensionMimeTypeDetector();
    }

    public function fileExists(string $path): bool
    {
        return $this->client->exists($path);
    }

    public function directoryExists(string $path): bool
    {
        return $this->client->directoryExists($path);
    }

    public function write(string $path, string $contents, Config $config): void
    {
        $this->client->upload($path, $contents);
    }

    public function writeStream(string $path, $contents, Config $config): void
    {
        $this->client->upload($path, $contents);
    }

    public function read(string $path): string
    {
        return $this->client->download($path);
    }

    public function readStream(string $path)
    {
        return $this->client->downloadStream($path);
    }

    public function delete(string $path): void
    {
        $this->client->delete($path);
    }

    public function deleteDirectory(string $path): void
    {
        // Bunny has no directory-delete API — enumerate all objects and delete individually.
        foreach ($this->listContents($path, true) as $item) {
            if ($item instanceof FileAttributes) {
                $this->client->delete($item->path());
            }
        }
    }

    public function createDirectory(string $path, Config $config): void
    {
        // Bunny Storage has no explicit directory creation; directories emerge from object paths.
    }

    public function setVisibility(string $path, string $visibility): void
    {
        // Bunny Storage does not support per-object visibility.
    }

    public function visibility(string $path): FileAttributes
    {
        return new FileAttributes($path);
    }

    public function mimeType(string $path): FileAttributes
    {
        $mime = $this->mimeDetector->detectMimeTypeFromPath($path) ?? 'application/octet-stream';

        return new FileAttributes($path, null, null, null, $mime);
    }

    public function lastModified(string $path): FileAttributes
    {
        $entry = $this->findEntry($path);

        return new FileAttributes($path, null, null, $entry['last_modified'] ?? null, null, ['checksum' => $entry['checksum'] ?? null]);
    }

    public function fileSize(string $path): FileAttributes
    {
        $entry = $this->findEntry($path);

        return new FileAttributes($path, $entry['size'] ?? null, null, null, null, ['checksum' => $entry['checksum'] ?? null]);
    }

    /** @return array{name: string, is_directory: bool, size: int, last_modified: int, checksum: string}|null */
    private function findEntry(string $path): ?array
    {
        $dir = dirname($path);
        $entries = $this->client->list($dir === '.' ? '' : $dir);

        foreach ($entries as $entry) {
            if ($entry['name'] === $path) {
                return $entry;
            }
        }

        return null;
    }

    public function listContents(string $path, bool $deep): iterable
    {
        $entries = $this->client->list($path);

        foreach ($entries as $entry) {
            if ($entry['is_directory']) {
                yield new DirectoryAttributes($entry['name']);
                if ($deep) {
                    yield from $this->listContents($entry['name'], true);
                }
            } else {
                yield new FileAttributes(
                    $entry['name'],
                    $entry['size'],
                    null,
                    $entry['last_modified'],
                    null,
                    ['checksum' => $entry['checksum']],
                );
            }
        }
    }

    public function move(string $source, string $destination, Config $config): void
    {
        $this->client->move($source, $destination);
    }

    public function copy(string $source, string $destination, Config $config): void
    {
        $this->client->copy($source, $destination);
    }
}
