<?php

declare(strict_types=1);

namespace Four\Flysystem\BunnyStorage\Client;

interface BunnyClientInterface
{
    public function upload(string $remotePath, mixed $content): void;

    public function download(string $remotePath): string;

    /** @return resource */
    public function downloadStream(string $remotePath): mixed;

    public function delete(string $remotePath): void;

    public function exists(string $remotePath): bool;

    /** @return array<int, array{name: string, is_directory: bool, size: int, last_modified: int}> */
    public function list(string $remotePath): array;

    public function move(string $from, string $to): void;

    public function copy(string $from, string $to): void;
}
