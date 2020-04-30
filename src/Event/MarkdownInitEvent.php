<?php

namespace App\Event;

use League\CommonMark\ConfigurableEnvironmentInterface;
use Symfony\Contracts\EventDispatcher\Event;

class MarkdownInitEvent extends Event {
    /**
     * @var ConfigurableEnvironmentInterface
     */
    private $environment;

    private $htmlPurifierConfig = [];

    public function __construct(ConfigurableEnvironmentInterface $environment) {
        $this->environment = $environment;
    }

    public function getEnvironment(): ConfigurableEnvironmentInterface {
        return $this->environment;
    }

    public function getHtmlPurifierConfig(): array {
        return $this->htmlPurifierConfig;
    }

    public function addHtmlPurifierConfig(array $config): void {
        $this->htmlPurifierConfig = $config + $this->htmlPurifierConfig;
    }
}
