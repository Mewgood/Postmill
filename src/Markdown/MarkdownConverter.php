<?php

namespace App\Markdown;

use App\Event\MarkdownCacheEvent;
use App\Event\MarkdownInitEvent;
use League\CommonMark\CommonMarkConverter;
use League\CommonMark\Environment;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Service for converting user-inputted Markdown (Markdown) to HTML.
 */
class MarkdownConverter {
    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    /**
     * @var CacheItemPoolInterface
     */
    private $cacheItemPool;

    public function __construct(
        EventDispatcherInterface $dispatcher,
        CacheItemPoolInterface $cacheItemPool
    ) {
        $this->dispatcher = $dispatcher;
        $this->cacheItemPool = $cacheItemPool;
    }

    public function convertToHtml(string $markdown): string {
        $environment = Environment::createCommonMarkEnvironment();
        $event = new MarkdownInitEvent($environment);

        $this->dispatcher->dispatch($event);

        $commonMarkConverter = new CommonMarkConverter([], $environment);

        $purifierConfig = \HTMLPurifier_Config::create($event->getHtmlPurifierConfig());
        $purifier = new \HTMLPurifier($purifierConfig);

        $html = $commonMarkConverter->convertToHtml($markdown);
        $html = $purifier->purify($html);

        return $html;
    }

    public function convertToHtmlCached(string $markdown): string {
        $event = new MarkdownCacheEvent();

        $this->dispatcher->dispatch($event);

        $key = sprintf(
            'cached_markdown.%s.%s',
            hash('sha256', $markdown),
            $event->getCacheKey()
        );

        $cacheItem = $this->cacheItemPool->getItem($key);

        if (!$cacheItem->isHit()) {
            $cacheItem->set($this->convertToHtml($markdown));

            $this->cacheItemPool->saveDeferred($cacheItem);
        }

        return $cacheItem->get();
    }
}
