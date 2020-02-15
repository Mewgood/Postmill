<?php

namespace App\Tests\Doctrine\Listener;

use App\Doctrine\Listener\DomainEventsListener;
use App\Entity\Contracts\VisibilityInterface;
use App\Entity\Forum;
use App\Entity\Submission;
use App\Entity\User;
use App\Event\ForumDeleted;
use App\Event\ForumUpdated;
use App\Event\SubmissionCreated;
use App\Event\SubmissionDeleted;
use App\Event\SubmissionUpdated;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Mapping\ClassMetadata;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @covers \App\Doctrine\Listener\DomainEventsListener
 */
class DomainEventsListenerTest extends TestCase {
    public function testDispatchesCreateEvent(): void {
        $entity = new Submission('a', null, null, new Forum('a', 'a', 'a', 'a'), new User('u', 'p'), null);

        /** @var EntityManagerInterface|\PHPUnit\Framework\MockObject\MockObject $entityManager */
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $args = new LifecycleEventArgs($entity, $entityManager);

        /** @var EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject $dispatcher */
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(function ($event) {
                return $event instanceof SubmissionCreated;
            }));

        $listener = new DomainEventsListener($dispatcher);
        $listener->postPersist($args);
    }

    public function testDispatchesDeleteEvent(): void {
        $entity = new Forum('Ringo', 'a', 'a', 'a');

        /** @var EntityManagerInterface|\PHPUnit\Framework\MockObject\MockObject $entityManager */
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $args = new LifecycleEventArgs($entity, $entityManager);

        /** @var EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject $dispatcher */
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(function ($event) {
                return $event instanceof ForumDeleted;
            }));

        $listener = new DomainEventsListener($dispatcher);
        $listener->postRemove($args);
    }

    public function testDispatchesUpdateEvent(): void {
        $entity = new Forum('Paul', 'a', 'a', 'a');

        $classMetadata = $this->createMock(ClassMetadata::class);
        $classMetadata
            ->method('getName')
            ->willReturn(Forum::class);

        /** @var EntityManagerInterface|\PHPUnit\Framework\MockObject\MockObject $entityManager */
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->method('getClassMetadata')
            ->willReturn($classMetadata);

        $changeSet = ['name' => ['John', 'Paul']];
        $preArgs = new PreUpdateEventArgs($entity, $entityManager, $changeSet);
        $postArgs = new LifecycleEventArgs($entity, $entityManager);

        /** @var EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject $dispatcher */
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(function ($event) {
                return $event instanceof ForumUpdated &&
                    $event->getBefore()->getName() === 'John' &&
                    $event->getAfter()->getName() === 'Paul';
            }));

        $listener = new DomainEventsListener($dispatcher);
        $listener->preUpdate($preArgs);
        $listener->postUpdate($postArgs);
    }

    public function testDispatchesDeleteEventWhenSoftDeleting(): void {
        $entity = new Submission('a', null, null, new Forum('a', 'a', 'a', 'a'), new User('u', 'p'), null);
        $entity->softDelete();

        $classMetadata = $this->createMock(ClassMetadata::class);
        $classMetadata
            ->method('getName')
            ->willReturn(Submission::class);

        /** @var EntityManagerInterface|\PHPUnit\Framework\MockObject\MockObject $entityManager */
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->method('getClassMetadata')
            ->willReturn($classMetadata);

        $changeSet = ['visibility' => [
            VisibilityInterface::VISIBILITY_VISIBLE,
            VisibilityInterface::VISIBILITY_DELETED, // not used
        ]];

        $preArgs = new PreUpdateEventArgs($entity, $entityManager, $changeSet);
        $postArgs = new LifecycleEventArgs($entity, $entityManager);

        /** @var EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject $dispatcher */
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher
            ->expects($this->exactly(2))
            ->method('dispatch')
            ->withConsecutive(
                [$this->isInstanceOf(SubmissionUpdated::class)],
                [$this->isInstanceOf(SubmissionDeleted::class)]
            );

        $listener = new DomainEventsListener($dispatcher);
        $listener->preUpdate($preArgs);
        $listener->postUpdate($postArgs);
    }
}
