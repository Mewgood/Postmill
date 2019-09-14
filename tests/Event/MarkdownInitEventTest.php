<?php

namespace App\Tests\Event;

use App\Event\MarkdownInitEvent;
use League\CommonMark\ConfigurableEnvironmentInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class MarkdownInitEventTest extends TestCase {
    /**
     * @var MockObject|ConfigurableEnvironmentInterface
     */
    private $environment;

    /**
     * @var MarkdownInitEvent
     */
    private $event;

    protected function setUp(): void {
        $this->environment = $this->createMock(ConfigurableEnvironmentInterface::class);

        $this->event = new MarkdownInitEvent($this->environment);
    }

    public function testPurifierConfig(): void {
        $this->event->addHtmlPurifierConfig(['foo' => 'bar']);
        $this->assertSame(['foo' => 'bar'], $this->event->getHtmlPurifierConfig());

        $this->event->addHtmlPurifierConfig([
            'foo' => 'woo',
            'shit' => 'poop',
        ]);

        $this->assertSame(['foo' => 'woo', 'shit' => 'poop'], $this->event->getHtmlPurifierConfig());
    }

    public function testConstructorArgumentGetters(): void {
        $this->assertSame($this->environment, $this->event->getEnvironment());
    }
}
