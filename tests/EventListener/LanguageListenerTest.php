<?php

namespace App\Tests\EventListener;

use App\Entity\Comment;
use App\Entity\Forum;
use App\Entity\Submission;
use App\Entity\User;
use App\Event\CommentCreated;
use App\Event\CommentUpdated;
use App\Event\SubmissionCreated;
use App\Event\SubmissionUpdated;
use App\EventListener\LanguageListener;
use App\Utils\LanguageDetector;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @covers \App\EventListener\LanguageListener
 */
class LanguageListenerTest extends TestCase {
    /**
     * @var LanguageDetector|\PHPUnit\Framework\MockObject\MockObject
     */
    private $detector;

    /**
     * @var EntityManagerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $entityManager;

    /**
     * @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $logger;

    /**
     * @var LanguageListener
     */
    private $listener;

    protected function setUp(): void {
        $this->detector = $this->createMock(LanguageDetector::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->listener = new LanguageListener($this->detector, $this->entityManager, $this->logger);
    }

    public function testSetsLanguageForNewSubmission(): void {
        $submission = $this->createSubmission();
        $event = new SubmissionCreated($submission);

        $this->expectLanguageDetected('title body');
        $this->listener->onSubmissionCreated($event);
        $this->assertLanguageSet($submission->getLanguage());
    }

    public function testDoesNotOverrideManuallySetLanguageOnNewSubmission(): void {
        $submission = $this->createSubmission(true);
        $event = new SubmissionCreated($submission);

        $this->expectNoLanguageDetection();
        $this->listener->onSubmissionCreated($event);
        $this->assertNoLanguageOverride($submission->getLanguage());
    }

    public function testSetsLanguageWhenUpdatingSubmission(): void {
        $submission = $this->createSubmission();
        $event = new SubmissionUpdated(clone $submission, $submission);

        $this->expectLanguageDetected('title body');
        $this->listener->onSubmissionUpdated($event);
        $this->assertLanguageSet($submission->getLanguage());
    }

    public function testDoesNotOverrideManuallySetLanguageWhenUpdatingSubmission(): void {
        $submission = $this->createSubmission(true);
        $event = new SubmissionUpdated(clone $submission, $submission);

        $this->expectNoLanguageDetection();
        $this->listener->onSubmissionUpdated($event);
        $this->assertNoLanguageOverride($submission->getLanguage());
    }

    public function testSetsLanguageForNewComment(): void {
        $comment = $this->createComment();
        $event = new CommentCreated($comment);

        $this->expectLanguageDetected('body');
        $this->listener->onCommentCreated($event);
        $this->assertLanguageSet($comment->getLanguage());
    }

    public function testDoesNotOverrideManuallySetLanguageOnNewComment(): void {
        $comment = $this->createComment(true);
        $event = new CommentCreated($comment);

        $this->expectNoLanguageDetection();
        $this->listener->onCommentCreated($event);
        $this->assertNoLanguageOverride($comment->getLanguage());
    }

    public function testSetsLanguageWhenUpdatingComment(): void {
        $comment = $this->createComment();
        $event = new CommentUpdated(clone $comment, $comment);

        $this->expectLanguageDetected('body');
        $this->listener->onCommentUpdated($event);
        $this->assertLanguageSet($comment->getLanguage());
    }

    public function testDoesNotOverrideManuallySetLanguageWhenUpdatingComment(): void {
        $comment = $this->createComment(true);
        $event = new CommentUpdated(clone $comment, $comment);

        $this->expectNoLanguageDetection();
        $this->listener->onCommentUpdated($event);
        $this->assertNoLanguageOverride($comment->getLanguage());
    }

    private function assertLanguageSet(string $language): void {
        $this->assertSame('en@autodetect', $language);
    }

    private function assertNoLanguageOverride($language): void {
        $this->assertSame('nb', $language);
    }

    private function expectLanguageDetected(string $expectedDocument): void {
        $this->detector
            ->expects($this->once())
            ->method('detect')
            ->with($expectedDocument)
            ->willReturnCallback(static function (string $input, float &$confidence = null) {
                $confidence = 0.69;

                return 'en';
            });

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $this->logger
            ->expects($this->once())
            ->method('info')
            ->with(
                'Language detection: best match was {lang} with {confidence}',
                ['lang' => 'en', 'confidence' => 0.69]
            );
    }

    private function expectNoLanguageDetection(): void {
        $this->detector
            ->expects($this->never())
            ->method('detect');

        $this->entityManager
            ->expects($this->never())
            ->method('flush');
    }

    private function createSubmission(bool $withLanguage = false): Submission {
        $submission = new Submission(
            'title',
            null,
            'body',
            new Forum('a', 'a', 'a', 'a'),
            new User('u', 'p'),
            null
        );

        if ($withLanguage) {
            $submission->setLanguage('nb');
        }

        return $submission;
    }

    private function createComment(bool $withLanguage = false): Comment {
        $comment = new Comment(
            'body',
            new User('u', 'p'),
            $this->createSubmission(),
            null
        );

        if ($withLanguage) {
            $comment->setLanguage('nb');
        }

        return $comment;
    }
}
