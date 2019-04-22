<?php

namespace App\Form\Type;

use App\Entity\Theme;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class ThemeSelectorType extends AbstractType {
    /**
     * @var array
     */
    private $themesConfig;

    public function __construct(array $themesConfig) {
        $this->themesConfig = $themesConfig;
    }

    public function configureOptions(OptionsResolver $resolver) {
        $resolver->setDefaults([
            'class' => Theme::class,
            'choice_label' => function (Theme $theme, $key, $value) {
                $name = $this->themesConfig[$theme->getConfigKey()]['name'];

                if ($this->themesConfig['_default'] === $theme->getConfigKey()) {
                    $name .= '*';
                }

                return $name;
            },
            'placeholder' => 'placeholder.default',
        ]);
    }

    public function getParent() {
        return EntityType::class;
    }
}
