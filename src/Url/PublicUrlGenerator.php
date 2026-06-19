<?php

declare(strict_types=1);

namespace Four\Flysystem\BunnyStorage\Url;

use Four\Flysystem\BunnyStorage\Config\BunnyStorageConfig;
use Four\Flysystem\BunnyStorage\Exception\UnableToGenerateUrl;
use League\Flysystem\Config;
use League\Flysystem\UrlGeneration\PublicUrlGenerator as FlysystemPublicUrlGenerator;

final class PublicUrlGenerator implements FlysystemPublicUrlGenerator
{
    public function __construct(private readonly BunnyStorageConfig $config) {}

    public function publicUrl(string $path, Config $config): string
    {
        if ($this->config->cdnHostname === null) {
            throw UnableToGenerateUrl::noCdnHostname($path);
        }

        return rtrim($this->config->cdnHostname, '/') . '/' . ltrim($path, '/');
    }
}
