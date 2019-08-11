<?php

namespace App\Tests\Flysystem;

use App\Flysystem\DsnAwareFilesystemFactory;
use League\Flysystem\Adapter\Local;
use League\Flysystem\AwsS3v3\AwsS3Adapter;
use PHPUnit\Framework\TestCase;

class DsnAwareFilesystemFactoryTest extends TestCase {
    public function testCreateLocalFilesystem(): void {
        $filesystem = DsnAwareFilesystemFactory::createFilesystem('file:///tmp');

        $this->assertInstanceOf(Local::class, $filesystem->getAdapter());
    }

    public function testCreateS3Filesystem(): void {
        $filesystem = DsnAwareFilesystemFactory::createFilesystem('s3://your-key:your-secret@your-region/bucket-name');

        /** @var AwsS3Adapter $adapter */
        $adapter = $filesystem->getAdapter();

        $this->assertInstanceOf(AwsS3Adapter::class, $adapter);
        $this->assertEquals('bucket-name', $adapter->getBucket());
        $this->assertEquals('your-region', $adapter->getClient()->getRegion());
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Unknown filesystem 'poop'
     */
    public function testThrowsOnUnrecognizedAdapter() {
        DsnAwareFilesystemFactory::createFilesystem('poop://crap');
    }
}
