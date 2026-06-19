<?php

declare(strict_types=1);

namespace Four\Flysystem\BunnyStorage\Client;

use Four\Flysystem\BunnyStorage\Config\BunnyStorageConfig;

final class BunnySdkClient implements BunnyClientInterface
{
    public function __construct(private readonly BunnyStorageConfig $config) {}

    public function upload(string $remotePath, mixed $content): void
    {
        // TODO: implement via bunnycdn/storage SDK
        throw new \LogicException('Not implemented');
    }

    public function download(string $remotePath): string
    {
        throw new \LogicException('Not implemented');
    }

    public function downloadStream(string $remotePath): mixed
    {
        throw new \LogicException('Not implemented');
    }

    public function delete(string $remotePath): void
    {
        throw new \LogicException('Not implemented');
    }

    public function exists(string $remotePath): bool
    {
        throw new \LogicException('Not implemented');
    }

    public function list(string $remotePath): array
    {
        throw new \LogicException('Not implemented');
    }

    public function move(string $from, string $to): void
    {
        throw new \LogicException('Not implemented');
    }

    public function copy(string $from, string $to): void
    {
        throw new \LogicException('Not implemented');
    }
}
