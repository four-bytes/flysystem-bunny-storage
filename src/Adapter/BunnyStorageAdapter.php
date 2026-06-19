<?php

declare(strict_types=1);

namespace Four\Flysystem\BunnyStorage\Adapter;

use Four\Flysystem\BunnyStorage\Client\BunnyClientInterface;
use League\Flysystem\Config;
use League\Flysystem\FileAttributes;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\StorageAttributes;

final class BunnyStorageAdapter implements FilesystemAdapter
{
    public function __construct(private readonly BunnyClientInterface $client) {}

    public function fileExists(string $path): bool
    {
        return $this->client->exists($path);
    }

    public function directoryExists(string $path): bool
    {
        return $this->client->exists(rtrim($path, '/') . '/');
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
        $this->client->delete(rtrim($path, '/') . '/');
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
        return new FileAttributes($path);
    }

    public function lastModified(string $path): FileAttributes
    {
        return new FileAttributes($path);
    }

    public function fileSize(string $path): FileAttributes
    {
        return new FileAttributes($path);
    }

    public function listContents(string $path, bool $deep): iterable
    {
        $entries = $this->client->list($path);

        foreach ($entries as $entry) {
            if ($entry['is_directory']) {
                yield new \League\Flysystem\DirectoryAttributes($entry['name']);
                if ($deep) {
                    yield from $this->listContents($entry['name'], true);
                }
            } else {
                yield new FileAttributes(
                    $entry['name'],
                    $entry['size'],
                    null,
                    $entry['last_modified'],
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
