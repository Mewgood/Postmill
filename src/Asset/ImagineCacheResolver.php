<?php

declare(strict_types=1);

namespace App\Asset;

use Liip\ImagineBundle\Binary\BinaryInterface;
use Liip\ImagineBundle\Imagine\Cache\Resolver\PsrCacheResolver;
use Liip\ImagineBundle\Imagine\Cache\Resolver\ResolverInterface;
use Psr\Cache\CacheItemPoolInterface;

/**
 * A caching resolver for Liip ImagineBundle.
 *
 * Unlike the bundled {@link PsrCacheResolver}, filesystem misses will also be
 * cached, preventing potentially slow filesystem access more than is necessary.
 */
final class ImagineCacheResolver implements ResolverInterface {
    /**
     * @var CacheItemPoolInterface
     */
    private $cacheItemPool;

    /**
     * @var ResolverInterface
     */
    private $resolver;

    public function __construct(
        CacheItemPoolInterface $cacheItemPool,
        ResolverInterface $resolver
    ) {
        $this->cacheItemPool = $cacheItemPool;
        $this->resolver = $resolver;
    }

    public function isStored($path, $filter): bool {
        $cacheKey = self::getCacheKey($path, $filter);
        $item = $this->cacheItemPool->getItem($cacheKey);

        if (!$item->isHit()) {
            $item->set($this->resolver->isStored($path, $filter));
            $this->cacheItemPool->saveDeferred($item);
        }

        return $item->get() !== false;
    }

    public function resolve($path, $filter): string {
        $cacheKey = self::getCacheKey($path, $filter);
        $item = $this->cacheItemPool->getItem($cacheKey);

        if (!$item->isHit() || !\is_string($item->get())) {
            $item->set($this->resolver->resolve($path, $filter));
            $this->cacheItemPool->saveDeferred($item);
        }

        return $item->get();
    }

    public function store(BinaryInterface $binary, $path, $filter): void {
        $this->resolver->store($binary, $path, $filter);

        $cacheKey = self::getCacheKey($path, $filter);
        $item = $this->cacheItemPool->getItem($cacheKey);
        $item->set($this->resolver->resolve($path, $filter));
        $this->cacheItemPool->saveDeferred($item);
    }

    public function remove(array $paths, array $filters): void {
        $cacheKeys = [];

        foreach ($paths as $path) {
            foreach ($filters as $filter) {
                $cacheKeys[] = self::getCacheKey($path, $filter);
            }
        }

        $this->cacheItemPool->deleteItems($cacheKeys);
        $this->resolver->remove($paths, $filters);
    }

    private static function getCacheKey(string $path, string $filter): string {
        return preg_replace('~[{}()/\\\\@:]~', '-', "$path-$filter");
    }
}
