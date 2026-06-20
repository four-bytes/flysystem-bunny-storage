<?php

declare(strict_types=1);

namespace Four\Flysystem\BunnyStorage\Client;

interface BunnyClientInterface
{
    /** @param string|resource $content */
    public function upload(string $path, mixed $content): void;

    public function download(string $path): string;

    /** @return resource */
    public function downloadStream(string $path): mixed;

    public function delete(string $path): void;

    public function exists(string $path): bool;

    public function directoryExists(string $path): bool;

    /** @return array<int, array{name: string, is_directory: bool, size: int, last_modified: int, checksum: string}> */
    public function list(string $path): array;

    public function move(string $from, string $to): void;

    public function copy(string $from, string $to): void;
}
