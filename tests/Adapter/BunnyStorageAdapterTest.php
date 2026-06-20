<?php

declare(strict_types=1);

namespace Four\Flysystem\BunnyStorage\Tests\Adapter;

use Four\Flysystem\BunnyStorage\Adapter\BunnyStorageAdapter;
use Four\Flysystem\BunnyStorage\Client\BunnyClientInterface;
use League\Flysystem\Config;
use League\Flysystem\DirectoryAttributes;
use League\Flysystem\FileAttributes;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class BunnyStorageAdapterTest extends TestCase
{
    private BunnyClientInterface&MockObject $client;
    private BunnyStorageAdapter $adapter;

    protected function setUp(): void
    {
        $this->client = $this->createMock(BunnyClientInterface::class);
        $this->adapter = new BunnyStorageAdapter($this->client);
    }

    public function testWriteDelegatesToClient(): void
    {
        $this->client->expects($this->once())->method('upload')->with('dir/file.txt', 'hello');

        $this->adapter->write('dir/file.txt', 'hello', new Config());
    }

    public function testWriteStreamDelegatesToClient(): void
    {
        $stream = fopen('php://memory', 'r+');
        if ($stream === false) {
            self::fail('Could not open memory stream');
        }
        $this->client->expects($this->once())->method('upload')->with('dir/file.txt', $stream);

        $this->adapter->writeStream('dir/file.txt', $stream, new Config());
        fclose($stream);
    }

    public function testReadDelegatesToClient(): void
    {
        $this->client->method('download')->with('dir/file.txt')->willReturn('contents');

        $this->assertSame('contents', $this->adapter->read('dir/file.txt'));
    }

    public function testReadStreamDelegatesToClient(): void
    {
        $stream = fopen('php://memory', 'r');
        if ($stream === false) {
            self::fail('Could not open memory stream');
        }
        $this->client->method('downloadStream')->with('dir/file.txt')->willReturn($stream);

        $this->assertSame($stream, $this->adapter->readStream('dir/file.txt'));
        fclose($stream);
    }

    public function testDeleteDelegatesToClient(): void
    {
        $this->client->expects($this->once())->method('delete')->with('dir/file.txt');

        $this->adapter->delete('dir/file.txt');
    }

    public function testDeleteDirectoryDeletesAllContainedFiles(): void
    {
        $this->client->method('list')->with('dir/sub')->willReturn([
            ['name' => 'dir/sub/a.txt', 'is_directory' => false, 'size' => 10, 'last_modified' => 0, 'checksum' => ''],
            ['name' => 'dir/sub/b.txt', 'is_directory' => false, 'size' => 20, 'last_modified' => 0, 'checksum' => ''],
        ]);
        $this->client->expects($this->exactly(2))->method('delete')
            ->with($this->logicalOr('dir/sub/a.txt', 'dir/sub/b.txt'));

        $this->adapter->deleteDirectory('dir/sub');
    }

    public function testDeleteDirectoryOnEmptyDirectoryDeletesNothing(): void
    {
        $this->client->method('list')->with('dir/empty')->willReturn([]);
        $this->client->expects($this->never())->method('delete');

        $this->adapter->deleteDirectory('dir/empty');
    }

    public function testFileExistsDelegatesToClient(): void
    {
        $this->client->method('exists')->with('file.txt')->willReturn(true);

        $this->assertTrue($this->adapter->fileExists('file.txt'));
    }

    public function testDirectoryExistsDelegatesToClientDirectoryExists(): void
    {
        $this->client->method('directoryExists')->with('dir')->willReturn(true);

        $this->assertTrue($this->adapter->directoryExists('dir'));
    }

    public function testMoveDelegatesToClient(): void
    {
        $this->client->expects($this->once())->method('move')->with('src.txt', 'dst.txt');

        $this->adapter->move('src.txt', 'dst.txt', new Config());
    }

    public function testCopyDelegatesToClient(): void
    {
        $this->client->expects($this->once())->method('copy')->with('src.txt', 'dst.txt');

        $this->adapter->copy('src.txt', 'dst.txt', new Config());
    }

    public function testCreateDirectoryIsNoOp(): void
    {
        $this->client->expects($this->never())->method($this->anything());

        $this->adapter->createDirectory('some/dir', new Config());
    }

    public function testSetVisibilityIsNoOp(): void
    {
        $this->client->expects($this->never())->method($this->anything());

        $this->adapter->setVisibility('file.txt', 'public');
    }

    public function testVisibilityReturnsFileAttributes(): void
    {
        $attrs = $this->adapter->visibility('file.txt');

        $this->assertInstanceOf(FileAttributes::class, $attrs);
        $this->assertSame('file.txt', $attrs->path());
    }

    public function testMimeTypeReturnsFileAttributes(): void
    {
        $attrs = $this->adapter->mimeType('file.txt');

        $this->assertInstanceOf(FileAttributes::class, $attrs);
        $this->assertSame('file.txt', $attrs->path());
    }

    public function testLastModifiedReturnsFileAttributes(): void
    {
        $attrs = $this->adapter->lastModified('file.txt');

        $this->assertInstanceOf(FileAttributes::class, $attrs);
    }

    public function testFileSizeReturnsFileAttributes(): void
    {
        $attrs = $this->adapter->fileSize('file.txt');

        $this->assertInstanceOf(FileAttributes::class, $attrs);
    }

    public function testListContentsFlat(): void
    {
        $this->client->method('list')->with('dir')->willReturn([
            ['name' => 'dir/a.txt', 'is_directory' => false, 'size' => 100, 'last_modified' => 1700000000, 'checksum' => 'abc123'],
            ['name' => 'dir/b.txt', 'is_directory' => false, 'size' => 200, 'last_modified' => 1700000001, 'checksum' => 'def456'],
        ]);

        $results = iterator_to_array($this->adapter->listContents('dir', false));

        $this->assertCount(2, $results);
        $this->assertInstanceOf(FileAttributes::class, $results[0]);
        $this->assertSame('dir/a.txt', $results[0]->path());
        $this->assertSame(100, $results[0]->fileSize());
        $this->assertSame('abc123', $results[0]->extraMetadata()['checksum']);
        $this->assertSame('def456', $results[1]->extraMetadata()['checksum']);
    }

    public function testListContentsFlatDoesNotRecurseIntoSubdirectories(): void
    {
        $this->client->expects($this->once())->method('list')->with('dir')->willReturn([
            ['name' => 'dir/sub/', 'is_directory' => true, 'size' => 0, 'last_modified' => 0, 'checksum' => ''],
        ]);

        $results = iterator_to_array($this->adapter->listContents('dir', false));

        $this->assertCount(1, $results);
        $this->assertInstanceOf(DirectoryAttributes::class, $results[0]);
    }

    public function testListContentsDeepRecursesIntoSubdirectories(): void
    {
        $this->client->method('list')->willReturnMap([
            ['dir', [
                ['name' => 'dir/sub/', 'is_directory' => true, 'size' => 0, 'last_modified' => 0, 'checksum' => ''],
                ['name' => 'dir/root.txt', 'is_directory' => false, 'size' => 10, 'last_modified' => 0, 'checksum' => ''],
            ]],
            ['dir/sub/', [
                ['name' => 'dir/sub/child.txt', 'is_directory' => false, 'size' => 5, 'last_modified' => 0, 'checksum' => ''],
            ]],
        ]);

        $results = iterator_to_array($this->adapter->listContents('dir', true), false);

        $paths = array_map(fn ($a) => $a->path(), $results);
        $this->assertContains('dir/sub', $paths); // DirectoryAttributes trims trailing slash
        $this->assertContains('dir/root.txt', $paths);
        $this->assertContains('dir/sub/child.txt', $paths);
    }
}
