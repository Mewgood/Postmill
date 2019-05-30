<?php

namespace App\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Serializer\Encoder\EncoderInterface;
use Symfony\Component\Serializer\SerializerInterface;

final class ResponseFixingListener implements EventSubscriberInterface {
    /**
     * @var SerializerInterface|EncoderInterface
     */
    private $serializer;

    public function __construct(SerializerInterface $serializer) {
        if (!$serializer instanceof EncoderInterface) {
            throw new \InvalidArgumentException(\sprintf(
                '$serializer must implement the "%s" interface',
                EncoderInterface::class
            ));
        }

        $this->serializer = $serializer;
    }

    /**
     * Add UTF-8 character set to XML and JSON responses that don't have this.
     */
    public function fixResponseCharset(ResponseEvent $event): void {
        if ($event->getRequestType() !== HttpKernelInterface::MASTER_REQUEST) {
            return;
        }

        $response = $event->getResponse();
        $contentType = $response->headers->get('Content-Type');
        $charset = $response->getCharset() ?: 'UTF-8';

        if (\preg_match('#[/+](?:json|xml)$#', $contentType)) {
            $contentType = \sprintf("%s; charset=%s", $contentType, $charset);

            $response->headers->set('Content-Type', $contentType);
        }
    }

    /**
     * Suppresses redirection when a controller throws a 403 exception during an
     * XHR request.
     *
     * This is necessary because Symfony by default has the shitty,
     * non-configurable behaviour of redirecting to the login page whenever
     * `AccessDeniedException` or `AuthenticationException` objects are thrown.
     * Making things worse, XMLHttpRequest is meant to follow redirects
     * silently, making it impossible to determine if a request was truly
     * successful.
     *
     * This listener fixes this issue by intercepting the offending exceptions
     * and sending an actual 403 response if the request happens through XHR.
     *
     * @see \Symfony\Component\Security\Http\Firewall\ExceptionListener
     */
    public function fixXhrExceptions(ExceptionEvent $event): void {
        $e = $event->getException();

        if (!$e instanceof AuthenticationException && !$e instanceof AccessDeniedException) {
            return;
        }

        $request = $event->getRequest();

        if (!$request->isXmlHttpRequest()) {
            return;
        }

        $format = $request->getRequestFormat();

        if ($this->serializer->supportsEncoding($format)) {
            $data = ['error' => $e->getMessage()];
            $responseBody = $this->serializer->serialize($data, $format);
        } else {
            // html and such
            $responseBody = $e->getMessage();
        }

        $event->setResponse(new Response($responseBody, 403));
    }

    public static function getSubscribedEvents() {
        return [
            KernelEvents::RESPONSE => ['fixResponseCharset', -10],
            KernelEvents::EXCEPTION => ['fixXhrExceptions', 1000],
        ];
    }
}
