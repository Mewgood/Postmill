<?php

namespace App\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Security;

class UserTimezoneExtension extends AbstractTypeExtension {
    /**
     * @var Security
     */
    private $security;

    public function __construct(Security $security) {
        $this->security = $security;
    }

    public function configureOptions(OptionsResolver $resolver): void {
        $resolver->setNormalizer('view_timezone', function (Options $options, $value) {
            if ($this->security->isGranted('ROLE_USER')) {
                $user = $this->security->getUser();
                \assert($user instanceof \App\Entity\User);

                return $user->getTimezone()->getName();
            }

            return $value;
        });
    }

    public static function getExtendedTypes(): iterable {
        return [DateTimeType::class, DateType::class];
    }
}
