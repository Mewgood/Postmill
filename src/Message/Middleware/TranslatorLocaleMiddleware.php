<?php

namespace App\Message\Middleware;

use App\Message\Stamp\RequestInfoStamp;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;
use Symfony\Contracts\Translation\LocaleAwareInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class TranslatorLocaleMiddleware implements MiddlewareInterface {
    /**
     * @var TranslatorInterface&LocaleAwareInterface
     */
    private $translator;

    /**
     * @param TranslatorInterface&LocaleAwareInterface $translator
     */
    public function __construct(TranslatorInterface $translator) {
        if (!$translator instanceof LocaleAwareInterface) {
            throw new \InvalidArgumentException(sprintf(
                '$translator must implement %s and %s',
                TranslatorInterface::class,
                LocaleAwareInterface::class,
            ));
        }

        /** @var TranslatorInterface&LocaleAwareInterface $translator */
        $this->translator = $translator;
    }

    public function handle(Envelope $envelope, StackInterface $stack): Envelope {
        /** @var RequestInfoStamp|null $requestInfo */
        $requestInfo = $envelope->last(RequestInfoStamp::class);

        if ($requestInfo) {
            $defaultLocale = $this->translator->getLocale();
            $this->translator->setLocale($requestInfo->getLocale());
        }

        try {
            return $stack->next()->handle($envelope, $stack);
        } finally {
            if ($requestInfo) {
                $this->translator->setLocale($defaultLocale);
            }
        }
    }
}
