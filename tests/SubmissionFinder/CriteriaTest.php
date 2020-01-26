<?php

namespace App\Tests\SubmissionFinder;

use App\Entity\Forum;
use App\Entity\Submission;
use App\Entity\User;
use App\SubmissionFinder\Criteria;
use PHPUnit\Framework\TestCase;

class CriteriaTest extends TestCase {
    public function testDefaults(): void {
        $user = new User('u', 'p');
        $criteria = new Criteria(Submission::SORT_HOT, $user);

        $this->assertEquals(Submission::SORT_HOT, $criteria->getSortBy());
        $this->assertEquals(Criteria::VIEW_ALL, $criteria->getView());
        $this->assertEquals(0, $criteria->getExclusions());
        $this->assertFalse($criteria->getStickiesFirst());
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

    public function testExcludeHiddenForumsCannotBeCalledTwice(): void {
        $this->expectException(\BadMethodCallException::class);
        $this->expectExceptionMessage('This method was already called');

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
        $forum1 = new Forum('a', 'a', 'a', 'a');
        $forum2 = new Forum('a', 'a', 'a', 'a');

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
     */
    public function testNoViewMethodCanBeCalledAfterAnother(string $first, string $second): void {
        $this->expectException(\BadMethodCallException::class);

        $this->createCriteria()->$first()->$second();
    }

    public function testSubscribedView(): void {
        $criteria = $this->createCriteria();
        $criteria->showSubscribed();

        $this->assertEquals(Criteria::VIEW_SUBSCRIBED, $criteria->getView());
    }

    public function testUserView(): void {
        $user1 = new User('u', 'p');
        $user2 = new User('u', 'p');

        $criteria = new Criteria(Submission::SORT_HOT);
        $criteria->showUsers($user1, $user2);

        $this->assertEquals(Criteria::VIEW_USERS, $criteria->getView());
        $this->assertEquals([$user1, $user2], $criteria->getUsers());
    }

    /**
     * @dataProvider provideGettersThatCannotBeAccessedBeforeMutatorHasBeenCalled
     */
    public function testCannotCallGetterMethodsWithoutHavingCalledTheirRespectiveMutators(string $getter): void {
        $this->expectException(\BadMethodCallException::class);

        $this->createCriteria()->$getter();
    }

    public function testThrowsOnInvalidSortMode(): void {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Unknown sort mode 'poop");

        new Criteria('poop');
    }

    /**
     * @dataProvider provideMethodsThatNeedUser
     *
     * @expectedException \BadMethodCallException
     * @expectedExceptionMessage No user was set
     */
    public function testMethodsThatNeedUserSetThrowExceptionWhenNoUserIsSet(string $method): void {
        $this->expectException(\BadMethodCallException::class);
        $this->expectExceptionMessage('No user was set');

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
            'showUsers',
        ];

        foreach ($viewMethods as $y) {
            foreach ($viewMethods as $x) {
                yield [$y, $x];
            }
        }
    }

    private function createCriteria(): Criteria {
        $user = new User('u', 'p');

        return new Criteria(Submission::SORT_HOT, $user);
    }
}
