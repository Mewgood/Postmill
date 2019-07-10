<?php

namespace App\Markdown;

use App\Markdown\Inline\Parser\CategoryLinkParser;
use App\Markdown\Inline\Parser\ForumLinkParser;
use App\Markdown\Inline\Parser\UserLinkParser;
use App\Markdown\Inline\Parser\WikiLinkParser;
use League\CommonMark\ConfigurableEnvironmentInterface;
use League\CommonMark\Extension\ExtensionInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class AppExtension implements ExtensionInterface {
    /**
     * @var UrlGeneratorInterface
     */
    private $urlGenerator;

    public function __construct(UrlGeneratorInterface $urlGenerator) {
        $this->urlGenerator = $urlGenerator;
    }

    public function register(ConfigurableEnvironmentInterface $environment): void {
        $environment->addInlineParser(new CategoryLinkParser($this->urlGenerator));
        $environment->addInlineParser(new ForumLinkParser($this->urlGenerator));
        $environment->addInlineParser(new UserLinkParser($this->urlGenerator));
        $environment->addInlineParser(new WikiLinkParser($this->urlGenerator));
    }
}
