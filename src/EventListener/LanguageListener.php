<?php

namespace App\EventListener;

use App\Entity\Comment;
use App\Entity\Submission;
use App\Event\CommentCreated;
use App\Event\CommentUpdated;
use App\Event\SubmissionCreated;
use App\Event\SubmissionUpdated;
use App\Utils\LanguageDetector;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class LanguageListener implements EventSubscriberInterface {
    /**
     * @var LanguageDetector
     */
    private $detector;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public static function getSubscribedEvents(): array {
        return [
            SubmissionCreated::class => ['onSubmissionCreated'],
            SubmissionUpdated::class => ['onSubmissionUpdated'],
            CommentCreated::class => ['onCommentCreated'],
            CommentUpdated::class => ['onCommentUpdated'],
        ];
    }

    public function __construct(
        LanguageDetector $detector,
        EntityManagerInterface $entityManager,
        ?LoggerInterface $logger
    ) {
        $this->detector = $detector;
        $this->entityManager = $entityManager;
        $this->logger = $logger ?? new NullLogger();
    }

    public function onSubmissionCreated(SubmissionCreated $event): void {
        $this->setLanguageForSubmission($event->getSubmission());
    }

    public function onSubmissionUpdated(SubmissionUpdated $event): void {
        $this->setLanguageForSubmission($event->getAfter());
    }

    public function onCommentCreated(CommentCreated $event): void {
        $this->setLanguageForComment($event->getComment());
    }

    public function onCommentUpdated(CommentUpdated $event): void {
        $this->setLanguageForComment($event->getAfter());
    }

    private function setLanguageForSubmission(Submission $submission): void {
        if ($this->isLanguageSetManually($submission->getLanguage())) {
            return;
        }

        $input = trim($submission->getTitle().' '.($submission->getBody() ?? ''));

        $submission->setLanguage($this->getLanguage($input));

        $this->entityManager->flush();
    }

    private function setLanguageForComment(Comment $comment): void {
        if ($this->isLanguageSetManually($comment->getLanguage())) {
            return;
        }

        $comment->setLanguage($this->getLanguage($comment->getBody()));

        $this->entityManager->flush();
    }

    private function getLanguage(string $input): ?string {
        if ($input === '') {
            return null;
        }

        $language = $this->detector->detect($input, $confidence);

        $this->logger->info(
            'Language detection: best match was {lang} with {confidence}',
            ['lang' => $language, 'confidence' => $confidence]
        );

        return $language !== null ? "$language@autodetect" : null;
    }

    private function isLanguageSetManually(?string $language): bool {
        // uses POSIX-style modifier to indicate auto-detected language
        return $language !== null && !str_contains($language, '@autodetect');
    }
}
