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

    private static $context = [
        'some_context' => 'foo',
        'other_context' => 'bar',
    ];

    protected function setUp() {
        $this->environment = $this->createMock(ConfigurableEnvironmentInterface::class);

        $this->event = new MarkdownInitEvent($this->environment, self::$context);
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
        $this->assertSame(self::$context, $this->event->getContext());
    }
}
