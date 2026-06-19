<?php

declare(strict_types=1);

namespace Four\Flysystem\BunnyStorage\Exception;

use League\Flysystem\FilesystemException;

final class TransientBunnyException extends \RuntimeException implements FilesystemException
{
    public static function fromStatusCode(int $statusCode, string $path): self
    {
        return new self(sprintf('Transient Bunny error %d for path "%s"', $statusCode, $path));
    }
}
