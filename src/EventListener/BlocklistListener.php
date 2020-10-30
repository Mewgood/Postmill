<?php

namespace App\EventListener;

use App\Entity\Blocklist;
use App\HttpClient\BlocklistClient;
use App\Repository\BlocklistRepository;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpClient\HttplugClient;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

class BlocklistListener implements EventSubscriberInterface {
    private const CACHE_KEY_FORMAT = 'blocklist_%s';
    private const CACHE_TAG = 'blocklist';

    /**
     * @var CacheItemPoolInterface $cache
     */
    private $cache;

    /**
     * @var BlocklistRepository
     */
    private $repository;

    /**
     * @var BlocklistClient
     */
    private $client;

    public static function getSubscribedEvents(): array {
        return [
            RequestEvent::class => ['onKernelRequest', -2],
        ];
    }

    public function __construct(
        CacheItemPoolInterface $cache,
        HttplugClient $client,
        BlocklistRepository $repository
    ) {
        $this->cache = $cache;
        $this->client = $client;
        $this->repository = $repository;
    }

    public function onKernelRequest(RequestEvent $event): void {

    }

    private function refresh(): array {
        // promise array

        foreach ($this->repository->findAll() as $blocklist) {
            $key = self::getCacheKey($blocklist);

            if (!$this->cache->hasItem($key)) {
                $responses[$key] = $this->client->request('GET', $blocklist->getUrl());
            }
        }

        foreach ($this->client->stream($responses) as $response => $chunk) {
        }
    }


    private static function getCacheKey(Blocklist $blocklist): string {
        return sprintf(self::CACHE_KEY_FORMAT, $blocklist->getId()->toString());
    }
}
