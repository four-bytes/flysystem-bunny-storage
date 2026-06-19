<?php

declare(strict_types=1);

namespace Four\Flysystem\BunnyStorage\Exception;

use League\Flysystem\FilesystemException;

final class UnableToGenerateUrl extends \RuntimeException implements FilesystemException
{
    public static function noCdnHostname(string $path): self
    {
        return new self(sprintf('No CDN hostname configured, cannot generate public URL for "%s"', $path));
    }
}
