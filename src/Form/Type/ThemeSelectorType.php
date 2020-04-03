<?php

namespace App\Form\Type;

use App\Entity\Theme;
use App\Repository\SiteRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class ThemeSelectorType extends AbstractType {
    /**
     * @var SiteRepository
     */
    private $siteRepository;

    /**
     * @var array
     */
    private $themesConfig;

    public function __construct(SiteRepository $siteRepository, array $themesConfig) {
        $this->siteRepository = $siteRepository;
        $this->themesConfig = $themesConfig;
    }

    public function configureOptions(OptionsResolver $resolver): void {
        $default = $this->siteRepository->findCurrentSite()->getDefaultTheme();
        $defaultKey = $default ? $default->getConfigKey() : $this->themesConfig['_default'];

        $resolver->setDefaults([
            'class' => Theme::class,
            'choice_label' => function (Theme $theme, $key, $value) use ($defaultKey) {
                $name = $this->themesConfig[$theme->getConfigKey()]['name'];

                if ($defaultKey === $theme->getConfigKey()) {
                    $name .= '*';
                }

                return $name;
            },
            'help' => 'help.theme_selector',
            'placeholder' => 'placeholder.default',
        ]);
    }

    public function getParent(): string {
        return EntityType::class;
    }
}
