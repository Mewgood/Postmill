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

    /**
     * @return array|false
     */
    public function write($path, $contents, Config $config) {
        $this->sleep();

        return $this->adapter->write($path, $contents, $config);
    }

    /**
     * @return array|false
     */
    public function writeStream($path, $resource, Config $config) {
        $this->sleep();

        return $this->adapter->writeStream($path, $resource, $config);
    }

    /**
     * @return array|false
     */
    public function update($path, $contents, Config $config) {
        $this->sleep();

        return $this->adapter->update($path, $contents, $config);
    }

    /**
     * @return array|false
     */
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

    /**
     * @return array|false
     */
    public function createDir($dirname, Config $config) {
        $this->sleep();

        return $this->adapter->createDir($dirname, $config);
    }

    /**
     * @return array|false
     */
    public function setVisibility($path, $visibility) {
        $this->sleep();

        return $this->adapter->setVisibility($path, $visibility);
    }

    /**
     * @return array|bool|null
     */
    public function has($path) {
        $this->sleep();

        return $this->adapter->has($path);
    }

    /**
     * @return array|false
     */
    public function read($path) {
        $this->sleep();

        return $this->adapter->read($path);
    }

    /**
     * @return array|false
     */
    public function readStream($path) {
        $this->sleep();

        return $this->adapter->readStream($path);
    }

    public function listContents($directory = '', $recursive = false): array {
        $this->sleep();

        return $this->adapter->listContents($directory, $recursive);
    }

    /**
     * @return array|false
     */
    public function getMetadata($path) {
        $this->sleep();

        return $this->adapter->getMetadata($path);
    }

    /**
     * @return array|false
     */
    public function getSize($path) {
        $this->sleep();

        return $this->adapter->getSize($path);
    }

    /**
     * @return array|false
     */
    public function getMimetype($path) {
        $this->sleep();

        return $this->adapter->getMimetype($path);
    }

    /**
     * @return array|false
     */
    public function getTimestamp($path) {
        $this->sleep();

        return $this->adapter->getTimestamp($path);
    }

    /**
     * @return array|false
     */
    public function getVisibility($path) {
        $this->sleep();

        return $this->adapter->getVisibility($path);
    }

    private function sleep(): void {
        usleep((int) ($this->sleepSeconds * 1000000));
    }
}
