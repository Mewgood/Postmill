<?php

namespace App\Tests\SubmissionFinder;

use App\Entity\Forum;
use App\Entity\Moderator;
use App\Entity\Submission;
use App\Entity\User;
use App\Repository\ForumRepository;
use App\Repository\SiteRepository;
use App\Repository\SubmissionRepository;
use App\Repository\UserRepository;
use App\SubmissionFinder\Criteria;
use App\SubmissionFinder\NoSubmissionsException;
use App\SubmissionFinder\SubmissionFinder;
use Doctrine\ORM\EntityManagerInterface;
use PagerWave\EntryReader\SimpleEntryReader;
use PagerWave\Extension\DateTime\DateTimeEntryReaderDecorator;
use PagerWave\Paginator;
use PagerWave\QueryReader\SymfonyRequestStackQueryReader;
use PagerWave\UrlGenerator\SymfonyRequestStackUrlGenerator;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Security;

/**
 * @covers \App\SubmissionFinder\SubmissionFinder
 */
class SubmissionFinderTest extends KernelTestCase {
    /**
     * @var Request
     */
    private $request;

    /**
     * @var Security|\PHPUnit\Framework\MockObject\MockObject
     */
    private $security;

    /**
     * @var SubmissionFinder
     */
    private $submissionFinder;

    protected function setUp(): void {
        self::bootKernel();

        $this->request = Request::create('/');
        $requestStack = new RequestStack();
        $requestStack->push($this->request);

        $this->security = $this->createMock(Security::class);

        $paginator = new Paginator(
            new DateTimeEntryReaderDecorator(new SimpleEntryReader()),
            new SymfonyRequestStackQueryReader($requestStack),
            new SymfonyRequestStackUrlGenerator($requestStack),
            new SimpleEntryReader()
        );

        $em = self::$container->get(EntityManagerInterface::class);
        $sites = self::$container->get(SiteRepository::class);
        $submissions = self::$container->get(SubmissionRepository::class);

        $this->submissionFinder = new SubmissionFinder(
            $em,
            $paginator,
            $requestStack,
            $this->security,
            $sites,
            $submissions
        );
    }

    public function testQueryWithEmptyResultsThrowsNotFoundException(): void {
        $this->expectException(NoSubmissionsException::class);

        $this->request->query->set('next', ['id' => 0]);

        $this->submissionFinder->find((new Criteria(Submission::SORT_NEW)));
    }

    public function testInvalidTimeFilterThrowsNotFoundException(): void {
        $this->expectException(NoSubmissionsException::class);

        $this->request->query->set('t', 'invalid');

        $this->submissionFinder->find(new Criteria(Submission::SORT_NEW));
    }

    public function provideTimeFilters(): \Generator {
        yield [Submission::TIME_ALL, 5];
        yield [Submission::TIME_YEAR, 4];
        yield [Submission::TIME_MONTH, 3];
        yield [Submission::TIME_WEEK, 2];
        yield [Submission::TIME_DAY, 1];
    }

    public function testShowForums(): void {
        /** @var Forum $forum */
        $forum = self::$container->get(ForumRepository::class)
            ->findOneByName('cats');

        $criteria = (new Criteria(Submission::SORT_NEW))
            ->showForums($forum);

        $submissions = $this->submissionFinder->find($criteria);

        $this->assertSame(['cats'], array_unique(array_map(function ($submission) {
            return $submission->getForum()->getName();
        }, iterator_to_array($submissions))));
    }

    public function testEmptyShowForums(): void {
        $criteria = (new Criteria(Submission::SORT_NEW))
            ->showForums();

        $this->assertEmpty($this->submissionFinder->find($criteria));
    }

    public function testShowUsers(): void {
        /** @var User $user */
        $user = self::$container->get(UserRepository::class)
            ->loadUserByUsername('emma');

        $criteria = (new Criteria(Submission::SORT_NEW))
            ->showUsers($user);

        $submissions = $this->submissionFinder->find($criteria);

        $this->assertSame(['emma'], array_unique(array_map(function ($submission) {
            return $submission->getUser()->getUsername();
        }, iterator_to_array($submissions))));
    }

    public function testEmptyShowUsers(): void {
        $criteria = (new Criteria(Submission::SORT_NEW))
            ->showUsers();

        $this->assertEmpty($this->submissionFinder->find($criteria));
    }

    public function testDelayedSubmissionsAreNotIncluded(): void {
        $submission = $this->createDelayedSubmission();

        $this->assertNotContains(
            $submission,
            $this->submissionFinder->find(new Criteria(Submission::SORT_NEW))
        );
    }

    public function testDelayedSubmissionAreIncludedIfOwnedByCurrentUser(): void {
        $submission = $this->createDelayedSubmission();

        $this->security
            ->expects($this->atLeastOnce())
            ->method('getUser')
            ->willReturn($submission->getUser());

        $this->assertContains(
            $submission,
            $this->submissionFinder->find(new Criteria(Submission::SORT_NEW))
        );
    }

    public function testDelayedSubmissionsAreIncludedIfForumModerator(): void {
        $currentUser = self::$container->get(UserRepository::class)->findOneByUsername('third');

        $this->security
            ->expects($this->atLeastOnce())
            ->method('getUser')
            ->willReturn($currentUser);

        $submission = $this->createDelayedSubmission();

        $moderator = new Moderator($submission->getForum(), $currentUser);
        $submission->getForum()->addModerator($moderator);

        self::$container->get(EntityManagerInterface::class)->flush();

        $this->assertContains(
            $submission,
            $this->submissionFinder->find(new Criteria(Submission::SORT_NEW))
        );
    }

    public function testDelayedSubmissionsAreIncludedIfAdmin(): void {
        $this->security
            ->expects($this->once())
            ->method('isGranted')
            ->with('ROLE_ADMIN')
            ->willReturn(true);

        $submission = $this->createDelayedSubmission();

        $this->assertContains(
            $submission,
            $this->submissionFinder->find(new Criteria(Submission::SORT_NEW))
        );
    }

    private function createDelayedSubmission(): Submission {
        $forum = self::$container->get(ForumRepository::class)->findOneByNormalizedName('cats');
        $user = self::$container->get(UserRepository::class)->findOneByUsername('zach');

        $submission = new Submission('Delayed', null, null, $forum, $user, null);
        $submission->setPublishedAt(new \DateTime('+30 hours'));

        $em = self::$container->get(EntityManagerInterface::class);
        $em->persist($submission);
        $em->flush();

        return $submission;
    }
}
