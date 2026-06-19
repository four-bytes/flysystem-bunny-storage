<?php

declare(strict_types=1);

namespace Four\Flysystem\BunnyStorage\Client;

use Bunny\Storage\AuthenticationException;
use Bunny\Storage\Client;
use Bunny\Storage\Exception as BunnyException;
use Bunny\Storage\FileNotFoundException;
use Four\Flysystem\BunnyStorage\Config\BunnyStorageConfig;
use Four\Flysystem\BunnyStorage\Exception\TransientBunnyException;
use League\Flysystem\UnableToCheckExistence;
use League\Flysystem\UnableToCopyFile;
use League\Flysystem\UnableToDeleteFile;
use League\Flysystem\UnableToMoveFile;
use League\Flysystem\UnableToReadFile;
use League\Flysystem\UnableToWriteFile;

final class BunnySdkClient implements BunnyClientInterface
{
    private readonly Client $sdk;
    private readonly string $storageZone;

    public function __construct(private readonly BunnyStorageConfig $config)
    {
        $this->sdk = new Client($config->apiKey, $config->storageZone, $config->region);
        $this->storageZone = $config->storageZone;
    }

    public function upload(string $path, mixed $content): void
    {
        try {
            if (is_resource($content)) {
                $body = stream_get_contents($content);
                if ($body === false) {
                    throw UnableToWriteFile::atLocation($path, 'could not read upload stream');
                }
                $this->sdk->putContents($path, $body);
            } else {
                $this->sdk->putContents($path, $content);
            }
        } catch (AuthenticationException $e) {
            throw UnableToWriteFile::atLocation($path, 'authentication failed', $e);
        } catch (BunnyException $e) {
            throw new TransientBunnyException("Upload failed for '{$path}': {$e->getMessage()}", 0, $e);
        }
    }

    public function download(string $path): string
    {
        try {
            return $this->sdk->getContents($path);
        } catch (AuthenticationException $e) {
            throw UnableToReadFile::fromLocation($path, 'authentication failed', $e);
        } catch (FileNotFoundException $e) {
            throw UnableToReadFile::fromLocation($path, 'file not found', $e);
        } catch (BunnyException $e) {
            throw new TransientBunnyException("Download failed for '{$path}': {$e->getMessage()}", 0, $e);
        }
    }

    public function downloadStream(string $path): mixed
    {
        $contents = $this->download($path);

        $stream = fopen('php://memory', 'r+');
        if ($stream === false) {
            throw UnableToReadFile::fromLocation($path, 'could not open memory stream');
        }

        fwrite($stream, $contents);
        rewind($stream);

        return $stream;
    }

    public function delete(string $path): void
    {
        try {
            $this->sdk->delete($path);
        } catch (FileNotFoundException) {
            // 404 on delete is a no-op — idempotent
        } catch (AuthenticationException $e) {
            throw UnableToDeleteFile::atLocation($path, 'authentication failed', $e);
        } catch (BunnyException $e) {
            throw new TransientBunnyException("Delete failed for '{$path}': {$e->getMessage()}", 0, $e);
        }
    }

    public function exists(string $path): bool
    {
        try {
            return $this->sdk->exists($path);
        } catch (AuthenticationException $e) {
            throw UnableToCheckExistence::forLocation($path, $e);
        } catch (BunnyException $e) {
            throw new TransientBunnyException("Exists check failed for '{$path}': {$e->getMessage()}", 0, $e);
        }
    }

    public function directoryExists(string $path): bool
    {
        try {
            $entries = $this->sdk->listFiles(rtrim($path, '/') . '/');
            return is_array($entries) && count($entries) > 0;
        } catch (AuthenticationException $e) {
            throw UnableToCheckExistence::forLocation($path, $e);
        } catch (BunnyException $e) {
            throw new TransientBunnyException("Directory exists check failed for '{$path}': {$e->getMessage()}", 0, $e);
        }
    }

    public function list(string $path): array
    {
        try {
            $raw = $this->sdk->listFiles($path === '' ? '/' : $path);
        } catch (AuthenticationException $e) {
            throw \League\Flysystem\UnableToListContents::atLocation($path, false, $e);
        } catch (BunnyException $e) {
            throw new TransientBunnyException("List failed for '{$path}': {$e->getMessage()}", 0, $e);
        }

        if (!is_array($raw)) {
            return [];
        }

        $entries = [];
        foreach ($raw as $item) {
            $entries[] = [
                'name' => $this->resolveEntryPath($item),
                'is_directory' => (bool) ($item['IsDirectory'] ?? false),
                'size' => (int) ($item['Length'] ?? 0),
                'last_modified' => isset($item['LastChanged']) ? (int) strtotime($item['LastChanged']) : 0,
            ];
        }

        return $entries;
    }

    public function copy(string $from, string $to): void
    {
        try {
            $contents = $this->sdk->getContents($from);
            $this->sdk->putContents($to, $contents);
        } catch (AuthenticationException $e) {
            throw UnableToCopyFile::fromLocationTo($from, $to, $e);
        } catch (FileNotFoundException $e) {
            throw UnableToCopyFile::fromLocationTo($from, $to, $e);
        } catch (BunnyException $e) {
            throw new TransientBunnyException("Copy failed from '{$from}' to '{$to}': {$e->getMessage()}", 0, $e);
        }
    }

    public function move(string $from, string $to): void
    {
        try {
            $this->copy($from, $to);
            $this->delete($from);
        } catch (UnableToCopyFile $e) {
            throw UnableToMoveFile::fromLocationTo($from, $to, $e);
        }
    }

    /** @param array<string, mixed> $item */
    private function resolveEntryPath(array $item): string
    {
        // Bunny Path = "/storageZone/dir/" — strip leading slash and zone prefix
        $dir = ltrim((string) ($item['Path'] ?? '/'), '/');
        $prefix = $this->storageZone . '/';

        if (str_starts_with($dir, $prefix)) {
            $dir = substr($dir, strlen($prefix));
        }

        return $dir . ($item['ObjectName'] ?? '');
    }
}
