<?php

namespace App\EventListener;

use App\Entity\ForumLogCommentDeletion;
use App\Entity\ForumLogSubmissionDeletion;
use App\Event\DeleteComment;
use App\Event\DeleteSubmission;
use App\Repository\SiteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class DeleteListener implements EventSubscriberInterface {
    public const FLUSH_LISTENER_PRIORITY = -128;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var SiteRepository
     */
    private $sites;

    public static function getSubscribedEvents(): array {
        return [
            DeleteSubmission::class => [
                ['onDeleteSubmission', 0],
                ['onDeleteSubmissionFlush', self::FLUSH_LISTENER_PRIORITY],
            ],
            DeleteComment::class => [
                ['onDeleteComment', 0],
                ['onDeleteCommentFlush', self::FLUSH_LISTENER_PRIORITY],
            ],
        ];
    }

    public function __construct(
        EntityManagerInterface $entityManager,
        SiteRepository $sites
    ) {
        $this->entityManager = $entityManager;
        $this->sites = $sites;
    }

    public function onDeleteSubmission(DeleteSubmission $event): void {
        $useTrash = $this->sites->findCurrentSite()->isTrashEnabled();
        $submission = $event->getSubmission();

        if ($useTrash && !$event->isPermanent() && $event->isModDelete()) {
            $submission->trash();
        } else {
            $hasVisibleComments = false;
            foreach ($submission->getComments() as $comment) {
                if ($comment->isVisible()) {
                    $hasVisibleComments = true;
                    break;
                }
            }

            if ($hasVisibleComments) {
                $submission->softDelete();
            } else {
                $this->entityManager->remove($submission);
            }
        }

        if ($event->isModDelete()) {
            $moderator = $event->getModerator();
            $reason = $event->getReason();

            $this->entityManager->persist(
                new ForumLogSubmissionDeletion($submission, $moderator, $reason)
            );
        }
    }

    public function onDeleteSubmissionFlush(DeleteSubmission $event): void {
        if (!$event->isNoFlush()) {
            $this->entityManager->flush();
        }
    }

    public function onDeleteComment(DeleteComment $event): void {
        $useTrash = $this->sites->findCurrentSite()->isTrashEnabled();
        $comments = [$event->getComment()];

        if ($event->isRecursive()) {
            foreach ($event->getComment()->getChildrenRecursive() as $child) {
                $comments[] = $child;
            }
        }

        $modDelete = $event->isModDelete();
        $moderator = $event->getModerator();
        $reason = $event->getReason();
        $permanent = $event->isPermanent();

        foreach ($comments as $comment) {
            if (!$permanent && $useTrash && $modDelete) {
                $comment->trash();
            } else {
                $comment->softDelete();

                if (!$comment->isThreadVisible()) {
                    $this->entityManager->remove($comment);
                }
            }

            if ($modDelete) {
                $this->entityManager->persist(
                    new ForumLogCommentDeletion($comment, $moderator, $reason)
                );
            }
        }
    }

    public function onDeleteCommentFlush(DeleteComment $event): void {
        if (!$event->isNoFlush()) {
            $this->entityManager->flush();
        }
    }
}
