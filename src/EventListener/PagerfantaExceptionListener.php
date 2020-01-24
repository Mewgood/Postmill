<?php

namespace App\EventListener;

use Pagerfanta\Exception\NotValidCurrentPageException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class PagerfantaExceptionListener implements EventSubscriberInterface {
    public static function getSubscribedEvents(): array {
        return [
            ExceptionEvent::class => ['onKernelException'],
        ];
    }

    public function onKernelException(ExceptionEvent $event): void {
        $e = $event->getThrowable();

        if ($e instanceof NotValidCurrentPageException) {
            $event->setThrowable(new NotFoundHttpException('Page not found', $e));
        }
    }
}
