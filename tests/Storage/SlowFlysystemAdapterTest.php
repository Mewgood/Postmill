<?php

declare(strict_types=1);

namespace App\Tests\Storage;

use App\Storage\SlowFlysystemAdapter;
use League\Flysystem\AdapterInterface;
use League\Flysystem\Config;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Storage\SlowFlysystemAdapter
 * @group time-sensitive
 */
final class SlowFlysystemAdapterTest extends TestCase {
    /**
     * @var AdapterInterface&\PHPUnit\Framework\MockObject\MockObject
     */
    private $decorated;

    /**
     * @var SlowFlysystemAdapter
     */
    private $adapter;

    /**
     * @var float
     */
    private $time;

    protected function setUp(): void {
        $this->decorated = $this->createMock(AdapterInterface::class);
        $this->adapter = new SlowFlysystemAdapter($this->decorated, 420.69);
        $this->time = microtime(true);
    }

    private function expectMethod(string $method, $return): void {
        $this->decorated
            ->expects($this->once())
            ->method($method)
            ->willReturn($return);
    }

    private function assertSlept(): void {
        $this->assertSame($this->time + 420.69, microtime(true));
    }

    public function testWrite(): void {
        $this->expectMethod('write', false);
        $this->assertFalse($this->adapter->write('foo', 'contents', new Config()));
        $this->assertSlept();
    }

    public function testWriteStream(): void {
        $this->expectMethod('writeStream', false);
        $this->assertFalse($this->adapter->writeStream('foo', fopen('php://memory', 'r'), new Config()));
        $this->assertSlept();
    }

    public function testUpdate(): void {
        $this->expectMethod('update', false);
        $this->assertFalse($this->adapter->update('foo', 'bar', new Config()));
        $this->assertSlept();
    }

    public function testUpdateStream(): void {
        $this->expectMethod('updateStream', false);
        $this->assertFalse($this->adapter->updateStream('foo', fopen('php://memory', 'r'), new Config()));
        $this->assertSlept();
    }

    public function testRename(): void {
        $this->expectMethod('rename', false);
        $this->assertFalse($this->adapter->rename('old', 'new'));
        $this->assertSlept();
    }

    public function testCopy(): void {
        $this->expectMethod('copy', false);
        $this->assertFalse($this->adapter->copy('old', 'new'));
        $this->assertSlept();
    }

    public function testDelete(): void {
        $this->expectMethod('delete', false);
        $this->assertFalse($this->adapter->delete('foo'));
        $this->assertSlept();
    }

    public function testDeleteDir(): void {
        $this->expectMethod('deleteDir', false);
        $this->assertFalse($this->adapter->deleteDir('foo'));
        $this->assertSlept();
    }

    public function testCreateDir(): void {
        $this->expectMethod('createDir', false);
        $this->assertFalse($this->adapter->createDir('foo', new Config()));
        $this->assertSlept();
    }

    public function testSetVisibility(): void {
        $this->expectMethod('setVisibility', false);
        $this->assertFalse($this->adapter->setVisibility('foo', 'bar'));
        $this->assertSlept();
    }

    public function testHas(): void {
        $this->expectMethod('has', false);
        $this->assertFalse($this->adapter->has('foo'));
        $this->assertSlept();
    }

    public function testRead(): void {
        $this->expectMethod('read', false);
        $this->assertFalse($this->adapter->read('read'));
        $this->assertSlept();
    }

    public function testReadStream(): void {
        $this->expectMethod('readStream', false);
        $this->assertFalse($this->adapter->readStream('foo'));
        $this->assertSlept();
    }

    public function testListContents(): void {
        $this->expectMethod('listContents', []);
        $this->assertSame([], $this->adapter->listContents('foo', true));
        $this->assertSlept();
    }

    public function testGetMetadata(): void {
        $this->expectMethod('getMetadata', false);
        $this->assertFalse($this->adapter->getMetadata('foo'));
        $this->assertSlept();
    }

    public function testGetSize(): void {
        $this->expectMethod('getSize', false);
        $this->assertFalse($this->adapter->getSize('foo'));
        $this->assertSlept();
    }

    public function testGetMimetype(): void {
        $this->expectMethod('getMimetype', false);
        $this->assertFalse($this->adapter->getMimetype('foo'));
        $this->assertSlept();
    }

    public function testGetTimestamp(): void {
        $this->expectMethod('getTimestamp', false);
        $this->assertFalse($this->adapter->getTimestamp('foo'));
        $this->assertSlept();
    }

    public function testGetVisibility(): void {
        $this->expectMethod('getVisibility', false);
        $this->assertFalse($this->adapter->getVisibility('foo'));
        $this->assertSlept();
    }
}
