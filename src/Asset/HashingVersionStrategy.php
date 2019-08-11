<?php

namespace App\Asset;

use Symfony\Component\Asset\VersionStrategy\VersionStrategyInterface;

final class HashingVersionStrategy implements VersionStrategyInterface {
    /**
     * @var string
     */
    private $webRoot;

    public function __construct(string $webRoot = __DIR__.'/../../public') {
        $this->webRoot = \rtrim($webRoot, '/');
    }

    public function getVersion($path): string {
        return \substr(\hash_file('sha256', $this->webRoot.'/'.$path), 0, 16);
    }

    public function applyVersion($path): string {
        $version = $this->getVersion($path);

        if (!$version) {
            return $path;
        }

        return \sprintf('%s?%s', $path, $this->getVersion($path));
    }
}
