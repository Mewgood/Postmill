<?php

namespace App\Form;

use App\DataObject\UserData;
use App\Form\EventListener\PasswordHashingSubscriber;
use App\Form\Type\HoneypotType;
use App\Repository\SiteRepository;
use App\Security\Authentication;
use Gregwar\CaptchaBundle\Type\CaptchaType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

final class UserType extends AbstractType {
    /**
     * @var UserPasswordHasherInterface
     */
    private $hasher;

    /**
     * @var SiteRepository
     */
    private $sites;

    /**
     * @var Authentication
     */
    private $authentication;

    /**
     * @var AuthorizationCheckerInterface
     */
    private $authorizationChecker;

    public function __construct(
        UserPasswordHasherInterface $hasher,
        SiteRepository $sites,
        Authentication $authentication,
        AuthorizationCheckerInterface $authorizationChecker
    ) {
        $this->hasher = $hasher;
        $this->sites = $sites;
        $this->authentication = $authentication;
        $this->authorizationChecker = $authorizationChecker;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void {
        if ($options['honeypot']) {
            $builder->add('phone', HoneypotType::class);
        }

        $editing = $builder->getData() && $builder->getData()->getId();

        $editUsername = true;
        if ($editing) {
            $user = $this->authentication->getUser();
            $editUsername = $this->authorizationChecker->isGranted('edit_username', $user);
        }

        $builder
            ->add('username', TextType::class, [
                'label' => 'label.username',
                'help' => 'user.username_rules',
                'disabled' => !$editUsername,
            ])
            ->add('password', RepeatedType::class, [
                'help' => 'user.password_rules',
                'property_path' => 'plainPassword',
                'required' => !$editing,
                'first_options' => ['label' => $editing ? 'label.new_password' : 'label.password'],
                'second_options' => ['label' => $editing ? 'label.repeat_new_password' : 'label.repeat_password'],
                'type' => PasswordType::class,
            ])
            ->add('email', EmailType::class, [
                'label' => 'label.email_address',
                'help' => 'user.email_optional',
                'required' => false,
            ]);

        if (!$editing && $this->sites->findCurrentSite()->isRegistrationCaptchaEnabled()) {
            $builder->add('verification', CaptchaType::class, [
                'label' => 'label.verification',
                'as_url' => true,
                'reload' => true,
            ]);
        }

        $builder->addEventSubscriber(new PasswordHashingSubscriber($this->hasher));
    }

    public function finishView(FormView $view, FormInterface $form, array $options): void {
        if ($form->getData() && $form->getData()->getId()) {
            // don't auto-complete the password fields when editing the user
            $view['password']['first']->vars['attr']['autocomplete'] = 'new-password';
            $view['password']['second']->vars['attr']['autocomplete'] = 'new-password';
        }
    }

    public function configureOptions(OptionsResolver $resolver): void {
        $resolver->setDefaults([
            'data_class' => UserData::class,
            'honeypot' => true,
            'validation_groups' => static function (FormInterface $form) {
                if ($form->getData()->getId() !== null) {
                    $groups[] = 'edit';
                } else {
                    $groups[] = 'registration';
                }

                return $groups;
            },
        ]);

        $resolver->setAllowedTypes('honeypot', ['bool']);
    }
}
