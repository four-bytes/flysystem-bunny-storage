<?php

declare(strict_types=1);

namespace Four\Flysystem\BunnyStorage\Tests\Url;

use Four\Flysystem\BunnyStorage\Config\BunnyStorageConfig;
use Four\Flysystem\BunnyStorage\Exception\UnableToGenerateUrl;
use Four\Flysystem\BunnyStorage\Url\PublicUrlGenerator;
use League\Flysystem\Config;
use PHPUnit\Framework\TestCase;

final class PublicUrlGeneratorTest extends TestCase
{
    public function testGeneratesPublicUrl(): void
    {
        $generator = new PublicUrlGenerator(
            new BunnyStorageConfig('key', 'zone', cdnHostname: 'https://cdn.example.com'),
        );

        $url = $generator->publicUrl('media/image.jpg', new Config());

        $this->assertSame('https://cdn.example.com/media/image.jpg', $url);
    }

    public function testStripsTrailingSlashFromHostname(): void
    {
        $generator = new PublicUrlGenerator(
            new BunnyStorageConfig('key', 'zone', cdnHostname: 'https://cdn.example.com/'),
        );

        $url = $generator->publicUrl('media/image.jpg', new Config());

        $this->assertSame('https://cdn.example.com/media/image.jpg', $url);
    }

    public function testStripsLeadingSlashFromPath(): void
    {
        $generator = new PublicUrlGenerator(
            new BunnyStorageConfig('key', 'zone', cdnHostname: 'https://cdn.example.com'),
        );

        $url = $generator->publicUrl('/media/image.jpg', new Config());

        $this->assertSame('https://cdn.example.com/media/image.jpg', $url);
    }

    public function testThrowsWhenNoCdnHostname(): void
    {
        $generator = new PublicUrlGenerator(
            new BunnyStorageConfig('key', 'zone'),
        );

        $this->expectException(UnableToGenerateUrl::class);

        $generator->publicUrl('media/image.jpg', new Config());
    }
}
