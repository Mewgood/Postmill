<?php

namespace App\Message\Handler;

use App\Entity\Comment;
use App\Entity\Contracts\VotableInterface;
use App\Entity\ForumSubscription;
use App\Entity\Message;
use App\Entity\Moderator;
use App\Entity\Submission;
use App\Entity\User;
use App\Entity\UserBlock;
use App\Event\DeleteSubmissionEvent;
use App\Message\DeleteUser;
use App\Repository\CommentRepository;
use App\Repository\MessageRepository;
use App\Repository\SubmissionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Delete user account.
 *
 * - Scramble username and password to prevent login
 * - Remove user's submissions
 * - Remove user's comments
 * - Remove user's votes
 * - Remove user's preferences, blocks, and hidden forums
 *
 * The deletion happens in batches, the size of which is given by the
 * `$batchSize` parameter.
 */
final class DeleteUserHandler implements MessageHandlerInterface {
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var MessageBusInterface
     */
    private $messageBus;

    /**
     * @var CommentRepository
     */
    private $comments;

    /**
     * @var MessageRepository
     */
    private $messages;

    /**
     * @var SubmissionRepository
     */
    private $submissions;

    /**
     * @var int
     */
    private $batchSize;

    public function __construct(
        EntityManagerInterface $entityManager,
        EventDispatcherInterface $eventDispatcher,
        MessageBusInterface $messageBus,
        CommentRepository $comments,
        MessageRepository $messages,
        SubmissionRepository $submissions,
        int $batchSize
    ) {
        $this->entityManager = $entityManager;
        $this->eventDispatcher = $eventDispatcher;
        $this->messageBus = $messageBus;
        $this->comments = $comments;
        $this->messages = $messages;
        $this->submissions = $submissions;
        $this->batchSize = $batchSize;
    }

    public function __invoke(DeleteUser $message): void {
        /** @var User|null $user */
        $user = $this->entityManager->find(User::class, $message->getUserId());

        if (!$user) {
            throw new UnrecoverableMessageHandlingException('User not found');
        }

        $dispatchAgain =
            $this->removeMetaData($user) ||
            $this->removeComments($user) ||
            $this->removeSubmissions($user) ||
            $this->removeCommentVotes($user) ||
            $this->removeSubmissionVotes($user) ||
            $this->removeMessages($user)
        ;

        $this->entityManager->clear();

        if ($dispatchAgain) {
            $this->messageBus->dispatch($message);
        }
    }

    public function removeMetaData(User $user): bool {
        if ($user->isAccountDeleted()) {
            return false;
        }

        $user->setUsername('!deleted'.$user->getId());
        $user->setEmail(null);
        $user->setPassword('');
        $user->setAdmin(false);
        $user->setLocale('en');
        $user->setBiography(null);
        $user->setAllowPrivateMessages(false);
        $user->setNightMode(false);
        $user->setNotifyOnReply(false);
        $user->setNotifyOnReply(false);
        $user->setOpenExternalLinksInNewTab(true);
        $user->setShowCustomStylesheets(true);
        $user->setShowThumbnails(true);
        $user->setFrontPage(Submission::FRONT_SUBSCRIBED);
        $user->setFrontPageSortMode(Submission::SORT_HOT);
        $user->setPreferredTheme(null);
        $user->setPreferredFonts(null);
        $user->setTimezone(new \DateTimeZone(date_default_timezone_get()));
        $user->setWhitelisted(false);

        $this->entityManager->createQueryBuilder()
            ->delete(ForumSubscription::class, 'fs')
            ->where('fs.user = ?1')
            ->setParameter(1, $user)
            ->getQuery()
            ->execute();

        $this->entityManager->createQueryBuilder()
            ->delete(Moderator::class, 'm')
            ->where('m.user = ?1')
            ->setParameter(1, $user)
            ->getQuery()
            ->execute();

        $this->entityManager->createQueryBuilder()
            ->delete(UserBlock::class, 'b')
            ->where('b.blocker = ?1')
            ->setParameter(1, $user)
            ->getQuery()
            ->execute();

        $this->entityManager->flush();

        return true;
    }

    public function removeSubmissions(User $user): bool {
        /** @var Submission[] $submissions */
        $submissions = $this->submissions->findBy([
            'user' => $user,
            'visibility' => Submission::VISIBILITY_VISIBLE,
        ], ['id' => 'DESC'], $this->batchSize);

        $dispatchAgain = false;

        foreach ($submissions as $submission) {
            $dispatchAgain = true;

            if (\count($submission->getComments()) > 0) {
                $submission->softDelete();
            } else {
                $this->entityManager->remove($submission);
            }
        }

        $this->entityManager->flush();

        $this->eventDispatcher->dispatch(new DeleteSubmissionEvent(...$submissions));

        return $dispatchAgain;
    }

    public function removeComments(User $user): bool {
        /** @var Comment[] $comments */
        $comments = $this->comments->findBy([
            'visibility' => Comment::VISIBILITY_VISIBLE,
            'user' => $user,
        ], ['id' => 'DESC'], $this->batchSize);

        $dispatchAgain = false;

        foreach ($comments as $comment) {
            $dispatchAgain = true;

            if (\count($comment->getChildren()) > 0) {
                $comment->softDelete();
            } else {
                $parent = $comment->getParent();

                if ($parent) {
                    $parent->removeReply($comment);
                }

                $this->entityManager->remove($comment);
            }
        }

        $this->entityManager->flush();

        return $dispatchAgain;
    }

    public function removeSubmissionVotes(User $user): bool {
        /** @var Submission[] $submissions */
        $submissions = $this->entityManager->createQueryBuilder()
            ->select('s')
            ->from(Submission::class, 's')
            ->join('s.votes', 'sv')
            ->where('sv.user = ?1')
            ->orderBy('s.id', 'DESC')
            ->setParameter(1, $user)
            ->setMaxResults($this->batchSize)
            ->getQuery()
            ->execute();

        $dispatchAgain = false;

        foreach ($submissions as $submission) {
            $dispatchAgain = true;

            $submission->vote(VotableInterface::VOTE_NONE, $user, null);
        }

        $this->entityManager->flush();

        return $dispatchAgain;
    }

    public function removeCommentVotes(User $user): bool {
        /** @var Comment[] $comments */
        $comments = $this->entityManager->createQueryBuilder()
            ->select('c')
            ->from(Comment::class, 'c')
            ->join('c.votes', 'cv')
            ->where('cv.user = ?1')
            ->orderBy('c.id', 'DESC')
            ->setParameter(1, $user)
            ->setMaxResults($this->batchSize)
            ->getQuery()
            ->execute();

        $dispatchAgain = false;

        foreach ($comments as $comment) {
            $dispatchAgain = true;

            $comment->vote(VotableInterface::VOTE_NONE, $user, null);
        }

        $this->entityManager->flush();

        return $dispatchAgain;
    }

    public function removeMessages(User $user): bool {
        /** @var Message[] $messages */
        $messages = $this->messages->findBy([
            'sender' => $user,
        ], ['timestamp' => 'DESC'], $this->batchSize);

        $dispatchAgain = false;

        foreach ($messages as $message) {
            $dispatchAgain = true;

            $thread = $message->getThread();
            $thread->removeMessage($message);

            if (\count($thread->getMessages()) === 0) {
                $this->entityManager->remove($thread);
            }
        }

        $this->entityManager->flush();

        return $dispatchAgain;
    }
}
