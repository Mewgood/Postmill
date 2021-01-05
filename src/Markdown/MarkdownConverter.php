<?php

namespace App\Markdown;

use App\Markdown\Event\ConvertMarkdown;
use Psr\EventDispatcher\EventDispatcherInterface;

class MarkdownConverter {
    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    public function __construct(EventDispatcherInterface $dispatcher) {
        $this->dispatcher = $dispatcher;
    }

    public function convertToHtml(string $markdown): string {
        $event = new ConvertMarkdown($markdown);

        $this->dispatcher->dispatch($event);

        return $event->getRenderedHtml();
    }
}
