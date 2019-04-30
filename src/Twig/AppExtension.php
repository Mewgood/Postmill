<?php

namespace App\Twig;

use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Twig extension which makes certain parameters available as template
 * functions.
 */
final class AppExtension extends AbstractExtension {
    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var string
     */
    private $siteName;

    /**
     * @var string|null
     */
    private $branch;

    /**
     * @var string|null
     */
    private $version;

    /**
     * @var bool
     */
    private $enableWebhooks;

    /**
     * @var array
     */
    private $fontsConfig;

    private $themesConfig;

    /**
     * @var string
     */
    private $uploadRoot;

    public function __construct(
        RequestStack $requestStack,
        string $siteName,
        ?string $branch,
        ?string $version,
        bool $enableWebhooks,
        array $fontsConfig,
        array $themesConfig,
        string $uploadRoot
    ) {
        $this->requestStack = $requestStack;
        $this->siteName = $siteName;
        $this->branch = $branch;
        $this->version = $version;
        $this->enableWebhooks = $enableWebhooks;
        $this->fontsConfig = $fontsConfig;
        $this->themesConfig = $themesConfig;
        $this->uploadRoot = $uploadRoot;
    }

    public function getFunctions(): array {
        return [
            new TwigFunction('site_name', function () {
                return $this->siteName;
            }),
            new TwigFunction('app_branch', function () {
                return $this->branch;
            }),
            new TwigFunction('app_version', function () {
                return $this->version;
            }),
            new TwigFunction('app_webhooks_enabled', function () {
                return $this->enableWebhooks;
            }),
            new TwigFunction('font_list', function (): array {
                return \array_keys($this->fontsConfig);
            }),
            new TwigFunction('font_names', function (string $font): array {
                $font = \strtolower($font);

                return $this->fontsConfig[$font]['alias'] ?? [$font];
            }),
            new TwigFunction('font_entrypoint', function (string $font): ?string {
                $font = \strtolower($font);

                return $this->fontsConfig[$font]['entrypoint'] ?? null;
            }),
            new TwigFunction('theme_list', function (): array {
                return \array_keys($this->fontsConfig);
            }),
            new TwigFunction('theme_entrypoint', function (string $name, bool $nightMode): ?string {
                if ($name === '_default') {
                    $name = $this->themesConfig['_default'];
                }

                $config = $this->themesConfig[\strtolower($name)];

                return $config['entrypoint'][$nightMode ? 'night' : 'day'] ?? $config['entrypoint'];
            }),
            new TwigFunction('upload_url', function (string $path) {
                $path = \rtrim($this->uploadRoot, '/').'/'.$path;

                if (\strpos($path, '//') === false) {
                    $request = $this->requestStack->getCurrentRequest();

                    $path = $request->getSchemeAndHttpHost().$path;
                }

                return $path;
            }),
        ];
    }
}
