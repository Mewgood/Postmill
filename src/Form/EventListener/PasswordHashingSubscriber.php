<?php

namespace App\Form\EventListener;

use App\DataObject\UserData;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class PasswordHashingSubscriber implements EventSubscriberInterface {
    private UserPasswordHasherInterface $hasher;

    public function __construct(UserPasswordHasherInterface $hasher) {
        $this->hasher = $hasher;
    }

    public function onPostSubmit(FormEvent $event): void {
        if (!$event->getForm()->isValid()) {
            return;
        }

        /* @var UserData $user */
        $user = $event->getForm()->getData();

        if (!$user instanceof UserData) {
            throw new \UnexpectedValueException(
                'Form data must be instance of '.UserData::class
            );
        }

        if ($user->getPlainPassword() !== null) {
            $hashed = $this->hasher->hashPassword($user, $user->getPlainPassword());
            $user->setPassword($hashed);
        }
    }

    public static function getSubscribedEvents(): array {
        return [
            FormEvents::POST_SUBMIT => ['onPostSubmit', -200],
        ];
    }
}
