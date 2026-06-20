<?php

declare(strict_types=1);

namespace Four\Flysystem\BunnyStorage\Tests\Client;

use Bunny\Storage\Client as BunnySdkClient;
use Bunny\Storage\Exception as BunnyException;
use Four\Flysystem\BunnyStorage\Client\AsyncBunnyClientInterface;
use Four\Flysystem\BunnyStorage\Client\BunnySdkClient as OurClient;
use Four\Flysystem\BunnyStorage\Config\BunnyStorageConfig;
use Four\Flysystem\BunnyStorage\Exception\TransientBunnyException;
use GuzzleHttp\Promise\FulfilledPromise;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Promise\RejectedPromise;
use League\Flysystem\UnableToWriteFile;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class BunnySdkClientAsyncTest extends TestCase
{
    private BunnySdkClient&MockObject $sdk;
    private OurClient $client;

    protected function setUp(): void
    {
        $this->sdk = $this->createMock(BunnySdkClient::class);
        $this->client = new OurClient(
            new BunnyStorageConfig('key', 'zone'),
            $this->sdk,
        );
    }

    public function testImplementsAsyncInterface(): void
    {
        $this->assertInstanceOf(AsyncBunnyClientInterface::class, $this->client);
    }

    public function testUploadAsyncWithStringReturnsPromise(): void
    {
        $this->sdk->method('uploadAsync')
            ->willReturn(new FulfilledPromise(null));

        $promise = $this->client->uploadAsync('test.txt', 'hello world');

        $this->assertInstanceOf(PromiseInterface::class, $promise);
        $promise->wait();
    }

    public function testUploadAsyncWithResourceReturnsPromise(): void
    {
        $stream = fopen('php://memory', 'r+');
        if ($stream === false) {
            self::fail('Could not open memory stream');
        }
        fwrite($stream, 'stream content');
        rewind($stream);

        $this->sdk->method('uploadAsync')
            ->willReturn(new FulfilledPromise(null));

        $promise = $this->client->uploadAsync('test.txt', $stream);

        $this->assertInstanceOf(PromiseInterface::class, $promise);
        $promise->wait();
        fclose($stream);
    }

    public function testUploadAsyncPassesLocalFilePathToSdk(): void
    {
        $capturedLocalPath = null;

        $this->sdk->expects($this->once())
            ->method('uploadAsync')
            ->with(
                $this->callback(function (string $localPath) use (&$capturedLocalPath): bool {
                    $capturedLocalPath = $localPath;
                    return file_exists($localPath);
                }),
                'dest.txt',
            )
            ->willReturn(new FulfilledPromise(null));

        $this->client->uploadAsync('dest.txt', 'data')->wait();

        // Temp file must be cleaned up after the promise settles
        $this->assertNotNull($capturedLocalPath);
        $this->assertFileDoesNotExist($capturedLocalPath);
    }

    public function testUploadAsyncCleansTempFileOnRejection(): void
    {
        $capturedLocalPath = null;
        $error = new \RuntimeException('network error');

        $this->sdk->expects($this->once())
            ->method('uploadAsync')
            ->with(
                $this->callback(function (string $localPath) use (&$capturedLocalPath): bool {
                    $capturedLocalPath = $localPath;
                    return true;
                }),
                'fail.txt',
            )
            ->willReturn(new RejectedPromise($error));

        try {
            $this->client->uploadAsync('fail.txt', 'data')->wait();
            $this->fail('Expected exception not thrown');
        } catch (TransientBunnyException $e) {
            $this->assertSame($error, $e->getPrevious());
        }

        $this->assertNotNull($capturedLocalPath);
        $this->assertFileDoesNotExist($capturedLocalPath);
    }

    public function testUploadAsyncWritesCorrectContentToTempFile(): void
    {
        $capturedLocalPath = null;

        $this->sdk->expects($this->once())
            ->method('uploadAsync')
            ->with(
                $this->callback(function (string $localPath) use (&$capturedLocalPath): bool {
                    $capturedLocalPath = $localPath;
                    return true;
                }),
                'content.txt',
            )
            ->willReturnCallback(function (string $localPath) use (&$capturedLocalPath): PromiseInterface {
                $capturedLocalPath = $localPath;
                $this->assertSame('expected content', file_get_contents($localPath));
                return new FulfilledPromise(null);
            });

        $this->client->uploadAsync('content.txt', 'expected content')->wait();
    }

    public function testUploadAsyncCleansTempFileOnSynchronousThrow(): void
    {
        $capturedLocalPath = null;
        $sdkError = new BunnyException('could not open local file');

        $this->sdk->expects($this->once())
            ->method('uploadAsync')
            ->with(
                $this->callback(function (string $localPath) use (&$capturedLocalPath): bool {
                    $capturedLocalPath = $localPath;
                    return true;
                }),
                'sync-fail.txt',
            )
            ->willThrowException($sdkError);

        try {
            $this->client->uploadAsync('sync-fail.txt', 'data');
            $this->fail('Expected TransientBunnyException not thrown');
        } catch (TransientBunnyException $e) {
            $this->assertSame($sdkError, $e->getPrevious());
        }

        $this->assertNotNull($capturedLocalPath);
        $this->assertFileDoesNotExist($capturedLocalPath);
    }
}
