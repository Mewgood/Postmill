<?php

namespace App\Utils;

use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\HttpFoundation\IpUtils;

/**
 * Utility class for rate-limiting by IP address.
 */
final class IpRateLimit {
    /**
     * @var CacheItemPoolInterface
     */
    private $cache;

    /**
     * @var array
     */
    private $ipWhitelist;

    /**
     * @var string
     */
    private $prefix;

    /**
     * @var int
     */
    private $maxHits;

    /**
     * @var \DateInterval
     */
    private $interval;

    public function __construct(
        CacheItemPoolInterface $rateLimitCache,
        array $ipWhitelist,
        string $prefix,
        int $maxHits,
        string $interval
    ) {
        $this->cache = $rateLimitCache;
        // FIXME: $ipWhitelist shouldn't contain null values
        $this->ipWhitelist = array_filter($ipWhitelist, 'is_string');
        $this->prefix = $prefix;
        $this->maxHits = $maxHits;
        $this->interval = @\DateInterval::createFromDateString($interval);

        if (!$this->interval) {
            throw new \InvalidArgumentException("Bad interval '$interval'");
        }
    }

    public function isExceeded(string $ip): bool {
        if ($this->isWhitelisted($ip)) {
            return false;
        }

        $cacheItem = $this->cache->getItem($this->getCacheKey($ip));

        return $cacheItem->isHit() && $cacheItem->get() > $this->maxHits;
    }

    public function increment(string $ip): void {
        if ($this->isWhitelisted($ip)) {
            return;
        }

        $cacheItem = $this->cache->getItem($this->getCacheKey($ip));
        $cacheItem->set(($cacheItem->get() ?? 0) + 1);
        $cacheItem->expiresAfter($this->interval);

        $this->cache->save($cacheItem);
    }

    public function reset(string $ip): void {
        $this->cache->deleteItem($this->getCacheKey($ip));
    }

    private function getCacheKey(string $ip): string {
        return $this->prefix.str_replace(':', 'x', '-'.$ip);
    }

    private function isWhitelisted(string $ip): bool {
        return IpUtils::checkIp($ip, $this->ipWhitelist);
    }
}
