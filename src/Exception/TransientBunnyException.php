<?php

declare(strict_types=1);

namespace Four\Flysystem\BunnyStorage\Exception;

use League\Flysystem\FilesystemException;

final class TransientBunnyException extends \RuntimeException implements FilesystemException
{
}
