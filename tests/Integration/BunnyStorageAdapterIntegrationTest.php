<?php

declare(strict_types=1);

namespace Four\Flysystem\BunnyStorage\Tests\Integration;

use Four\Flysystem\BunnyStorage\Adapter\BunnyStorageAdapter;
use Four\Flysystem\BunnyStorage\Client\BunnySdkClient;
use Four\Flysystem\BunnyStorage\Config\BunnyStorageConfig;
use League\Flysystem\Config;
use League\Flysystem\Filesystem;
use PHPUnit\Framework\TestCase;

/**
 * Real-life integration tests against a live Bunny Storage zone.
 *
 * Requires environment variables:
 *   BUNNY_API_KEY         — storage zone password (not account API key)
 *   BUNNY_STORAGE_ZONE    — storage zone name
 *   BUNNY_REGION          — optional; defaults to "de"
 *
 * Skip with:  BUNNY_API_KEY="" php vendor/bin/phpunit
 * Run with:   BUNNY_API_KEY=xxx BUNNY_STORAGE_ZONE=yyy php vendor/bin/phpunit --group integration
 */
#[\PHPUnit\Framework\Attributes\Group('integration')]
final class BunnyStorageAdapterIntegrationTest extends TestCase
{
    private static ?Filesystem $fs = null;
    private static string $prefix;

    protected function setUp(): void
    {
        $apiKey = getenv('BUNNY_API_KEY');
        $zone   = getenv('BUNNY_STORAGE_ZONE');

        if (!$apiKey || !$zone) {
            $this->markTestSkipped('Set BUNNY_API_KEY and BUNNY_STORAGE_ZONE to run integration tests.');
        }

        if (self::$fs === null) {
            $region = getenv('BUNNY_REGION') ?: 'de';
            $config = new BunnyStorageConfig($apiKey, $zone, $region);
            $client = new BunnySdkClient($config);
            $adapter = new BunnyStorageAdapter($client);
            self::$fs = new Filesystem($adapter);
        }

        self::$prefix = 'integration-test-' . bin2hex(random_bytes(8)) . '/';
    }

    protected function tearDown(): void
    {
        // Best-effort cleanup of everything written under the test prefix.
        if (self::$fs !== null && isset(self::$prefix)) {
            try {
                self::$fs->deleteDirectory(self::$prefix);
            } catch (\Throwable) {
                // ignore cleanup failures
            }
        }
    }

    public function testWriteAndRead(): void
    {
        $path = self::$prefix . 'hello.txt';

        self::$fs->write($path, 'hello world');

        $this->assertSame('hello world', self::$fs->read($path));
    }

    public function testFileExists(): void
    {
        $path = self::$prefix . 'exists.txt';

        $this->assertFalse(self::$fs->fileExists($path));

        self::$fs->write($path, 'data');

        $this->assertTrue(self::$fs->fileExists($path));
    }

    public function testDelete(): void
    {
        $path = self::$prefix . 'delete.txt';

        self::$fs->write($path, 'to delete');
        self::$fs->delete($path);

        $this->assertFalse(self::$fs->fileExists($path));
    }

    public function testCopy(): void
    {
        $src = self::$prefix . 'src.txt';
        $dst = self::$prefix . 'dst.txt';

        self::$fs->write($src, 'copy me');
        self::$fs->copy($src, $dst);

        $this->assertSame('copy me', self::$fs->read($dst));
        $this->assertTrue(self::$fs->fileExists($src));
    }

    public function testMove(): void
    {
        $src = self::$prefix . 'move-src.txt';
        $dst = self::$prefix . 'move-dst.txt';

        self::$fs->write($src, 'move me');
        self::$fs->move($src, $dst);

        $this->assertFalse(self::$fs->fileExists($src));
        $this->assertSame('move me', self::$fs->read($dst));
    }

    public function testWriteAndReadStream(): void
    {
        $path   = self::$prefix . 'stream.txt';
        $source = fopen('php://memory', 'r+');
        assert($source !== false);
        fwrite($source, 'streamed content');
        rewind($source);

        self::$fs->writeStream($path, $source);
        fclose($source);

        $stream = self::$fs->readStream($path);
        $this->assertSame('streamed content', stream_get_contents($stream));
        fclose($stream);
    }

    public function testListContents(): void
    {
        self::$fs->write(self::$prefix . 'a.txt', 'a');
        self::$fs->write(self::$prefix . 'b.txt', 'b');

        $items = iterator_to_array(self::$fs->listContents(rtrim(self::$prefix, '/'), false), false);

        $names = array_map(fn ($i) => $i->path(), $items);
        $this->assertContains(rtrim(self::$prefix, '/') . '/a.txt', $names);
        $this->assertContains(rtrim(self::$prefix, '/') . '/b.txt', $names);
    }

    public function testListContentsDeep(): void
    {
        self::$fs->write(self::$prefix . 'sub/nested.txt', 'nested');

        $items = iterator_to_array(
            self::$fs->listContents(rtrim(self::$prefix, '/'), true),
            false,
        );

        $names = array_map(fn ($i) => $i->path(), $items);
        $this->assertTrue(
            count(array_filter($names, fn ($n) => str_ends_with($n, 'nested.txt'))) > 0,
            'Deep listing must find nested.txt',
        );
    }

    public function testFileSize(): void
    {
        $path = self::$prefix . 'sized.txt';
        $body = 'size test body';

        self::$fs->write($path, $body);

        $this->assertSame(strlen($body), self::$fs->fileSize($path)->fileSize());
    }

    public function testLastModified(): void
    {
        $path = self::$prefix . 'modified.txt';

        self::$fs->write($path, 'ts');

        $ts = self::$fs->lastModified($path)->lastModified();
        $this->assertNotNull($ts);
        $this->assertGreaterThan(0, $ts);
    }

    public function testMimeType(): void
    {
        $path = self::$prefix . 'image.png';

        self::$fs->write($path, 'fake png bytes');

        $this->assertSame('image/png', self::$fs->mimeType($path)->mimeType());
    }
}
