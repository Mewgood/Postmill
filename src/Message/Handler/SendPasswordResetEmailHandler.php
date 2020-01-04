<?php

namespace App\Message\Handler;

use App\Entity\User;
use App\Message\SendPasswordResetEmail;
use App\Repository\UserRepository;
use App\Security\PasswordResetHelper;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Contracts\Translation\TranslatorInterface;

final class SendPasswordResetEmailHandler implements MessageHandlerInterface {
    /**
     * @var PasswordResetHelper
     */
    private $helper;

    /**
     * @var MailerInterface
     */
    private $mailer;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var UserRepository
     */
    private $users;

    /**
     * @var string
     */
    private $noReplyAddress;

    /**
     * @var string
     */
    private $siteName;

    public function __construct(
        PasswordResetHelper $helper,
        MailerInterface $mailer,
        UserRepository $users,
        TranslatorInterface $translator,
        string $noReplyAddress,
        string $siteName
    ) {
        $this->mailer = $mailer;
        $this->helper = $helper;
        $this->users = $users;
        $this->translator = $translator;
        $this->noReplyAddress = $noReplyAddress;
        $this->siteName = $siteName;
    }

    public function __invoke(SendPasswordResetEmail $message): void {
        $users = $this->users->lookUpByEmail($message->getEmailAddress());

        if (!$users) {
            throw new UnrecoverableMessageHandlingException('No users found');
        }

        $links = array_map(function (User $user) {
            return [
                'username' => $user->getUsername(),
                'url' => $this->helper->generateResetUrl($user),
            ];
        }, $users);

        $mail = (new TemplatedEmail())
            ->to(new Address($message->getEmailAddress(), $users[0]->getUsername()))
            ->from(new Address($this->noReplyAddress, $this->siteName))
            ->subject($this->translator->trans('reset_password_email.subject', [
                '%site_name%' => $this->siteName,
            ]))
            ->textTemplate('reset_password/email.txt.twig')
            ->context(['links' => $links])
        ;

        // TODO: X-Originating-IP

        $this->mailer->send($mail);
    }
}
