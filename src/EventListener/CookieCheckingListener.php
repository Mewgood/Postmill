<?php

declare(strict_types=1);

namespace App\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Twig\Environment as Twig;

/**
 * Ensure that the browser doesn't discard cookies before showing the login
 * page. Unbelievably, some people will install browser extensions that block
 * all cookies in the name of privacy, then come complaining when things break.
 *
 * This works as follows:
 *
 * 1. Check that a session is started or a marker cookie exists. If so, the
 *    login page is displayed. Otherwise...
 *
 * 2. Check that there's a query param containing a recent timestamp.
 *
 *    - If the param does not exist, a redirect with the marker cookie and a
 *      timestamped query param occurs. Go back to step 1.
 *
 *    - If the param does exist, indicating a redirect did indeed happen, an
 *      error message urging the user to enable cookies is displayed.
 */
class CookieCheckingListener implements EventSubscriberInterface {
    private const ATTRIBUTE_KEY = '_cookie_check';

    private Twig $twig;

    public static function getSubscribedEvents(): array {
        return [
            KernelEvents::REQUEST => 'onKernelRequest',
        ];
    }

    public function __construct(Twig $twig) {
        $this->twig = $twig;
    }

    public function onKernelRequest(RequestEvent $event): void {
        $request = $event->getRequest();

        if ($request->attributes->get('_route') !== 'login') {
            return;
        }

        $hasCookie = $request->hasPreviousSession() ||
            $request->cookies->getInt(self::ATTRIBUTE_KEY);

        if ($hasCookie) {
            return;
        }

        $redirected = $request->query->get(self::ATTRIBUTE_KEY) > time() - 10;

        if (!$redirected) {
            $event->setResponse($this->createRedirectResponse($request));
        } else {
            $event->setResponse($this->createErrorResponse());
        }
    }

    private function createRedirectResponse(Request $request): RedirectResponse {
        $location = $request->getSchemeAndHttpHost().
            $request->getBaseUrl().
            $request->getPathInfo().'?'.
            http_build_query(array_replace(
                $request->query->all(),
                [self::ATTRIBUTE_KEY => time()],
            ));

        $response = new RedirectResponse($location);
        $response->headers->setCookie(
            Cookie::create(self::ATTRIBUTE_KEY)
                ->withValue('1')
                ->withPath($request->getBasePath().$request->getPathInfo())
        );

        return $response;
    }

    private function createErrorResponse(): Response {
        return new Response(
            $this->twig->render('user/cookie_message.html.twig'),
            Response::HTTP_FORBIDDEN,
        );
    }
}
