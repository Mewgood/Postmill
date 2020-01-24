<?php

namespace App\Tests\EventListener;

use App\EventListener\PagerfantaExceptionListener;
use Pagerfanta\Exception\OutOfRangeCurrentPageException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class PagerfantaExceptionListenerTest extends TestCase {
    public function testSetsExceptionOnPagerfantaException(): void {
        $pagerException = new OutOfRangeCurrentPageException();

        $event = $this->createMock(ExceptionEvent::class);
        $event
            ->expects($this->once())
            ->method('getThrowable')
            ->willReturn($pagerException);
        $event
            ->expects($this->once())
            ->method('setThrowable')
            ->with(
                $this->callback(function (NotFoundHttpException $e) use ($pagerException) {
                    return $e->getPrevious() === $pagerException;
                })
            );

        (new PagerfantaExceptionListener())->onKernelException($event);
    }

    public function testIgnoresExceptionIfNotPagerfanta(): void {
        $event = $this->createMock(ExceptionEvent::class);
        $event
            ->expects($this->once())
            ->method('getThrowable')
            ->willReturn(new \Exception());
        $event
            ->expects($this->never())
            ->method('setThrowable');

        (new PagerfantaExceptionListener())->onKernelException($event);
    }
}
