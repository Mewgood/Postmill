<?php

namespace App\EventListener;

use App\Controller\BanLandingPageController;
use App\Entity\User;
use App\Repository\IpBanRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Show the user a landing page if they are banned.
 */
final class BanListener implements EventSubscriberInterface {
    /**
     * @var IpBanRepository
     */
    private $repository;

    /**
     * @var AuthorizationCheckerInterface
     */
    private $authorizationChecker;

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    public static function getSubscribedEvents(): array {
        return [
            // the priority must be less than 8, as the token storage won't be
            // populated otherwise!
            KernelEvents::REQUEST => ['onKernelRequest', 4],
        ];
    }

    public function __construct(
        IpBanRepository $repository,
        AuthorizationCheckerInterface $authorizationChecker,
        TokenStorageInterface $tokenStorage
    ) {
        $this->repository = $repository;
        $this->authorizationChecker = $authorizationChecker;
        $this->tokenStorage = $tokenStorage;
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

        if ($this->authorizationChecker->isGranted('ROLE_USER')) {
            /* @var User $user */
            $user = $this->tokenStorage->getToken()->getUser();

            if ($user->isBanned()) {
                $this->setController($request);

                return;
            }

            if ($user->isWhitelistedOrAdmin()) {
                // don't check for ip bans
                return;
            }
        }

        if ($this->repository->ipIsBanned($request->getClientIp())) {
            $this->setController($request);
        }
    }

    private function setController(Request $request): void {
        $request->attributes->set('_controller', BanLandingPageController::class);
    }
}
