<?php

declare(strict_types=1);

namespace Four\Flysystem\BunnyStorage\Config;

final readonly class BunnyStorageConfig
{
    public function __construct(
        public string $apiKey,
        public string $storageZone,
        public string $region = 'de',
        public ?string $cdnHostname = null,
    ) {}
}
