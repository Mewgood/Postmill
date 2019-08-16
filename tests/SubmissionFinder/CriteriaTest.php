<?php

namespace App\Tests\SubmissionFinder;

use App\Entity\Forum;
use App\Entity\Submission;
use App\Entity\User;
use App\SubmissionFinder\Criteria;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CriteriaTest extends TestCase {
    public function testDefaults(): void {
        /** @var User|MockObject $user */
        $user = $this->createMock(User::class);
        $criteria = new Criteria(Submission::SORT_HOT, $user);

        $this->assertEquals(Submission::SORT_HOT, $criteria->getSortBy());
        $this->assertEquals(Criteria::VIEW_ALL, $criteria->getView());
        $this->assertEquals(0, $criteria->getExclusions());
        $this->assertEquals(false, $criteria->getStickiesFirst());
        $this->assertEquals(25, $criteria->getMaxPerPage());
        $this->assertSame($user, $criteria->getUser());
    }

    /**
     * @doesNotPerformAssertions
     * @dataProvider provideSortModes
     */
    public function testAcceptsValidSortModes(string $sortMode): void {
        new Criteria($sortMode);
    }

    public function testExcludeHiddenForums(): void {
        $criteria = $this->createCriteria();
        $criteria->excludeHiddenForums();

        $this->assertEquals(
            Criteria::EXCLUDE_HIDDEN_FORUMS,
            $criteria->getExclusions() & Criteria::EXCLUDE_HIDDEN_FORUMS
        );
    }

    public function testExcludeWithoutUserSetResultsInNoExclusion(): void {
        $criteria = (new Criteria(Submission::SORT_HOT))->excludeHiddenForums();

        $this->assertEquals(0, $criteria->getExclusions());
    }

    /**
     * @expectedException \BadMethodCallException
     * @expectedExceptionMessage This method was already called
     */
    public function testExcludeHiddenForumsCannotBeCalledTwice(): void {
        $this->createCriteria()
            ->excludeHiddenForums()
            ->excludeHiddenForums();
    }

    public function testStickies(): void {
        $criteria = $this->createCriteria()->stickiesFirst();

        $this->assertTrue($criteria->getStickiesFirst());
    }

    public function testFeaturedView(): void {
        $criteria = $this->createCriteria()->showFeatured();

        $this->assertEquals(Criteria::VIEW_FEATURED, $criteria->getView());
    }

    public function testForumView(): void {
        /** @var Forum|MockObject $forum1 */
        $forum1 = $this->createMock(Forum::class);

        /** @var Forum|MockObject $forum2 */
        $forum2 = $this->createMock(Forum::class);

        $criteria = $this->createCriteria()->showForums($forum1, $forum2);

        $this->assertEquals(Criteria::VIEW_FORUMS, $criteria->getView());
        $this->assertEquals([$forum1, $forum2], $criteria->getForums());
    }

    public function testMaxPerPage(): void {
        $criteria = $this->createCriteria()->setMaxPerPage(69);

        $this->assertEquals(69, $criteria->getMaxPerPage());
    }

    public function testModeratedView(): void {
        $criteria = $this->createCriteria()->showModerated();

        $this->assertEquals(Criteria::VIEW_MODERATED, $criteria->getView());
    }

    /**
     * @dataProvider provideViewMethodMatrix
     * @expectedException \BadMethodCallException
     */
    public function testNoViewMethodCanBeCalledAfterAnother(string $first, string $second): void {
        $this->createCriteria()->$first()->$second();
    }

    public function testSubscribedView(): void {
        $criteria = $this->createCriteria();
        $criteria->showSubscribed();

        $this->assertEquals(Criteria::VIEW_SUBSCRIBED, $criteria->getView());
    }

    public function testUserView(): void {
        /** @var User|MockObject $user1 */
        $user1 = $this->createMock(User::class);

        /** @var User|MockObject $user2 */
        $user2 = $this->createMock(User::class);

        $criteria = new Criteria(Submission::SORT_HOT);
        $criteria->showUsers($user1, $user2);

        $this->assertEquals(Criteria::VIEW_USERS, $criteria->getView());
        $this->assertEquals([$user1, $user2], $criteria->getUsers());
    }

    /**
     * @dataProvider provideGettersThatCannotBeAccessedBeforeMutatorHasBeenCalled
     * @expectedException \BadMethodCallException
     */
    public function testCannotCallGetterMethodsWithoutHavingCalledTheirRespectiveMutators(string $getter): void {
        $this->createCriteria()->$getter();
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Unknown sort mode 'poop'
     */
    public function testThrowsOnInvalidSortMode(): void {
        new Criteria('poop');
    }

    /**
     * @dataProvider provideMethodsThatNeedUser
     *
     * @expectedException \BadMethodCallException
     * @expectedExceptionMessage No user was set
     */
    public function testMethodsThatNeedUserSetThrowExceptionWhenNoUserIsSet(string $method): void {
        (new Criteria(Submission::SORT_HOT))->$method();
    }

    public function provideMethodsThatNeedUser(): iterable {
        yield ['getUser'];
        yield ['showModerated'];
        yield ['showSubscribed'];
    }

    public function provideGettersThatCannotBeAccessedBeforeMutatorHasBeenCalled(): iterable {
        yield ['getForums'];
        yield ['getUsers'];
    }

    public function provideSortModes(): iterable {
        return array_map(function ($mode) {
            return [$mode];
        }, Submission::SORT_OPTIONS);
    }

    public function provideViewMethodMatrix(): iterable {
        $viewMethods = [
            'showFeatured',
            'showSubscribed',
            'showModerated',
            'showForums',
            'showUsers'
        ];

        foreach ($viewMethods as $y) {
            foreach ($viewMethods as $x) {
                yield [$y, $x];
            }
        }
    }

    private function createCriteria(): Criteria {
        /** @var User|MockObject $user */
        $user = $this->createMock(User::class);

        return new Criteria(Submission::SORT_HOT, $user);
    }
}
