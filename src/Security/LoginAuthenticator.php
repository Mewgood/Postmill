<?php

namespace App\Security;

use App\Entity\User;
use App\Security\Exception\IpRateLimitedException;
use App\Utils\IpRateLimit;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\InvalidCsrfTokenException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

/**
 * Guard authenticator for username/password login that does things a bit
 * differently from Symfony.
 *
 * - Rate limit IPs on login.
 *
 * @todo two-factor authentication
 * @todo login tokens stored in db so users can see where they're logged in
 * @todo rehash passwords if password_needs_rehash is true
 */
final class LoginAuthenticator extends AbstractGuardAuthenticator {
    use TargetPathTrait;

    /**
     * @var CsrfTokenManagerInterface
     */
    private $csrfTokenManager;

    /**
     * @var IpRateLimit
     */
    private $rateLimit;

    /**
     * @var UserPasswordEncoderInterface
     */
    private $passwordEncoder;

    /**
     * @var UrlGeneratorInterface
     */
    private $urlGenerator;

    public function __construct(
        CsrfTokenManagerInterface $csrfTokenManager,
        IpRateLimit $loginRateLimit,
        UserPasswordEncoderInterface $passwordEncoder,
        UrlGeneratorInterface $urlGenerator
    ) {
        $this->csrfTokenManager = $csrfTokenManager;
        $this->rateLimit = $loginRateLimit;
        $this->urlGenerator = $urlGenerator;
        $this->passwordEncoder = $passwordEncoder;
    }

    public function start(Request $request, AuthenticationException $authException = null): Response {
        if ($request->isMethod('GET') && !$request->isXmlHttpRequest()) {
            $this->saveTargetPath($request->getSession(), 'main', $request->getUri());
        }

        return new RedirectResponse($this->urlGenerator->generate('login'));
    }

    public function supports(Request $request): bool {
        return $request->attributes->get('_route') === 'login_check' &&
            $request->isMethod('POST');
    }

    public function getCredentials(Request $request): array {
        $token = new CsrfToken('authenticate', $request->request->get('_csrf_token'));

        if (!$this->csrfTokenManager->isTokenValid($token)) {
            throw new InvalidCsrfTokenException('Invalid CSRF token');
        }

        if ($this->rateLimit->isExceeded($request->getClientIp())) {
            throw new IpRateLimitedException();
        }

        return [
            'username' => $request->request->get('_username'),
            'password' => $request->request->get('_password'),
        ];
    }

    public function getUser($credentials, UserProviderInterface $userProvider): ?User {
        $user = $userProvider->loadUserByUsername($credentials['username']);
        \assert($user instanceof User);

        return $user;
    }

    public function checkCredentials($credentials, UserInterface $user): bool {
        return $this->passwordEncoder->isPasswordValid($user, $credentials['password']);
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response {
        $session = $request->getSession();
        $session->set(Security::AUTHENTICATION_ERROR, $exception);
        $session->set('remember_me', $request->request->getBoolean('_remember_me'));

        $username = $request->request->get('_username');

        if (\strlen($username) <= Security::MAX_USERNAME_LENGTH) {
            $session->set(Security::LAST_USERNAME, $username);
        }

        $this->rateLimit->increment($request->getClientIp());

        return new RedirectResponse($this->urlGenerator->generate('login'));
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey): Response {
        $targetPath = $this->getTargetPath($request->getSession(), $providerKey);

        if (!$targetPath) {
            $targetPath = $this->urlGenerator->generate('front');
        }

        return new RedirectResponse($targetPath);
    }

    public function supportsRememberMe(): bool {
        return true;
    }
}
