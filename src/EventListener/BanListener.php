<?php

namespace App\EventListener;

use App\Entity\User;
use App\Repository\IpBanRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Security;

/**
 * Show the user a landing page if they are banned.
 */
final class BanListener implements EventSubscriberInterface {
    /**
     * @var IpBanRepository
     */
    private $repository;

    /**
     * @var Security
     */
    private $security;

    /**
     * @var UrlGeneratorInterface
     */
    private $urlGenerator;

    public static function getSubscribedEvents(): array {
        return [
            // the priority must be less than 8, as the token storage won't be
            // populated otherwise!
            KernelEvents::REQUEST => ['onKernelRequest', 4],
        ];
    }

    public function __construct(
        IpBanRepository $repository,
        Security $security,
        UrlGeneratorInterface $urlGenerator
    ) {
        $this->repository = $repository;
        $this->security = $security;
        $this->urlGenerator = $urlGenerator;
    }

    public function onKernelRequest(RequestEvent $event): void {
        $request = $event->getRequest();

        // Don't check for bans on subrequests or requests that are 'safe' (i.e.
        // they're considered read-only). As only POST/PUT/etc. requests should
        // result in the state of the application mutating, banned users should
        // not be able to do any damage with GET/HEAD requests.
        if (!$event->isMasterRequest() || $request->isMethodSafe()) {
            return;
        }

        if ($this->security->isGranted('ROLE_USER')) {
            $user = $this->security->getUser();
            \assert($user instanceof User);

            if ($user->isBanned()) {
                $event->setResponse($this->getRedirectResponse());

                return;
            }

            if ($user->isWhitelistedOrAdmin()) {
                // don't check for ip bans
                return;
            }
        }

        if ($this->repository->ipIsBanned($request->getClientIp())) {
            $event->setResponse($this->getRedirectResponse());
        }
    }

    private function getRedirectResponse(): RedirectResponse {
        return new RedirectResponse($this->urlGenerator->generate('banned'));
    }
}
