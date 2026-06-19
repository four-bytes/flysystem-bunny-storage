<?php

declare(strict_types=1);

namespace Four\Flysystem\BunnyStorage\Tests\Config;

use Four\Flysystem\BunnyStorage\Config\BunnyStorageConfig;
use PHPUnit\Framework\TestCase;

final class BunnyStorageConfigTest extends TestCase
{
    public function testDefaultRegionIsDe(): void
    {
        $config = new BunnyStorageConfig('key', 'zone');

        $this->assertSame('de', $config->region);
    }

    public function testDefaultCdnHostnameIsNull(): void
    {
        $config = new BunnyStorageConfig('key', 'zone');

        $this->assertNull($config->cdnHostname);
    }

    public function testCustomValues(): void
    {
        $config = new BunnyStorageConfig(
            apiKey: 'secret',
            storageZone: 'myzone',
            region: 'uk',
            cdnHostname: 'https://cdn.example.com',
        );

        $this->assertSame('secret', $config->apiKey);
        $this->assertSame('myzone', $config->storageZone);
        $this->assertSame('uk', $config->region);
        $this->assertSame('https://cdn.example.com', $config->cdnHostname);
    }
}
