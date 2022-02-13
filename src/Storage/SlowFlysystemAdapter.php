<?php

declare(strict_types=1);

namespace App\Storage;

use League\Flysystem\AdapterInterface;
use League\Flysystem\Config;

/**
 * Slows down any adapter. Intended as a testing tool to gauge how the
 * application would react to a slow storage backend.
 */
class SlowFlysystemAdapter implements AdapterInterface {
    /**
     * @var AdapterInterface
     */
    private $adapter;

    /**
     * @var float
     */
    private $sleepSeconds;

    public function __construct(AdapterInterface $adapter, float $sleepSeconds) {
        $this->adapter = $adapter;
        $this->sleepSeconds = $sleepSeconds;
    }

    public function write($path, $contents, Config $config) {
        $this->sleep();

        return $this->adapter->write($path, $contents, $config);
    }

    public function writeStream($path, $resource, Config $config) {
        $this->sleep();

        return $this->adapter->writeStream($path, $resource, $config);
    }

    public function update($path, $contents, Config $config) {
        $this->sleep();

        return $this->adapter->update($path, $contents, $config);
    }

    public function updateStream($path, $resource, Config $config) {
        $this->sleep();

        return $this->adapter->updateStream($path, $resource, $config);
    }

    public function rename($path, $newpath): bool {
        $this->sleep();

        return $this->adapter->rename($path, $newpath);
    }

    public function copy($path, $newpath): bool {
        $this->sleep();

        return $this->adapter->copy($path, $newpath);
    }

    public function delete($path): bool {
        $this->sleep();

        return $this->adapter->delete($path);
    }

    public function deleteDir($dirname): bool {
        $this->sleep();

        return $this->adapter->deleteDir($dirname);
    }

    public function createDir($dirname, Config $config) {
        $this->sleep();

        return $this->adapter->createDir($dirname, $config);
    }

    public function setVisibility($path, $visibility) {
        $this->sleep();

        return $this->adapter->setVisibility($path, $visibility);
    }

    public function has($path) {
        $this->sleep();

        return $this->adapter->has($path);
    }

    public function read($path) {
        $this->sleep();

        return $this->adapter->read($path);
    }

    public function readStream($path) {
        $this->sleep();

        return $this->adapter->readStream($path);
    }

    public function listContents($directory = '', $recursive = false): array {
        $this->sleep();

        return $this->adapter->listContents($directory, $recursive);
    }

    public function getMetadata($path) {
        $this->sleep();

        return $this->adapter->getMetadata($path);
    }

    public function getSize($path) {
        $this->sleep();

        return $this->adapter->getSize($path);
    }

    public function getMimetype($path) {
        $this->sleep();

        return $this->adapter->getMimetype($path);
    }

    public function getTimestamp($path) {
        $this->sleep();

        return $this->adapter->getTimestamp($path);
    }

    public function getVisibility($path) {
        $this->sleep();

        return $this->adapter->getVisibility($path);
    }

    private function sleep(): void {
        usleep((int) ($this->sleepSeconds * 1000000));
    }
}
