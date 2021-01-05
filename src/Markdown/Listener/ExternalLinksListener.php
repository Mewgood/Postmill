<?php

namespace App\Markdown\Listener;

use App\Entity\User;
use App\Markdown\Event\BuildCacheContext;
use App\Markdown\Event\ConfigureCommonMark;
use App\Utils\TrustedHosts;
use League\CommonMark\Extension\ExternalLink\ExternalLinkExtension;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Security;

/**
 * Configures the rendering of external links.
 */
final class ExternalLinksListener implements EventSubscriberInterface {
    public const HOST_REGEX_CONTEXT_KEY = 'host_regex';
    public const OPEN_IN_NEW_TAB_CONTEXT_KEY = 'open_external_links_in_new_tab';

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var Security
     */
    private $security;

    /**
     * @var TrustedHosts
     */
    private $trustedHosts;

    public static function getSubscribedEvents(): array {
        return [
            BuildCacheContext::class => [
                ['addOpenInNewTabContext'],
                ['addHostRegexContext'],
            ],
            ConfigureCommonMark::class => [
                ['onConfigureCommonMark'],
            ],
        ];
    }

    public function __construct(
        Security $security,
        RequestStack $requestStack,
        TrustedHosts $trustedHosts
    ) {
        $this->requestStack = $requestStack;
        $this->security = $security;
        $this->trustedHosts = $trustedHosts;
    }

    public function addOpenInNewTabContext(BuildCacheContext $event): void {
        if ($this->shouldOpenInNewTab()) {
            $event->addToContext(self::OPEN_IN_NEW_TAB_CONTEXT_KEY);
        }
    }

    public function addHostRegexContext(BuildCacheContext $event): void {
        $event->addToContext(self::HOST_REGEX_CONTEXT_KEY, $this->getHostRegex());
    }

    public function onConfigureCommonMark(ConfigureCommonMark $event): void {
        $event->getEnvironment()->addExtension(new ExternalLinkExtension());
        $event->getEnvironment()->mergeConfig([
            'external_link' => [
                'internal_hosts' => $this->getHostRegex(),
                'nofollow' => 'external',
                'noopener' => 'external',
                'noreferrer' => 'external',
                'open_in_new_window' => $this->shouldOpenInNewTab(),
            ],
        ]);
    }

    private function shouldOpenInNewTab(): bool {
        $user = $this->security->getUser();
        \assert($user instanceof User || $user === null);

        return $user ? $user->openExternalLinksInNewTab() : false;
    }

    private function getHostRegex(): string {
        $hostRegexFragments = $this->trustedHosts->getRegexFragments();

        if (!$hostRegexFragments) {
            $request = $this->requestStack->getCurrentRequest();
            $host = $request ? $request->getHost() : null;

            if ($host !== null) {
                $hostRegexFragments = TrustedHosts::makeRegexFragments($host);
            }
        }

        if (!$hostRegexFragments) {
            // don't match any hosts as being internal
            return '/(?!)/';
        }

        sort($hostRegexFragments);

        return '/'.implode('|', $hostRegexFragments).'/';
    }
}
