<?php

namespace App\Tests\SubmissionFinder;

use App\Entity\Forum;
use App\Entity\Submission;
use App\Entity\User;
use App\SubmissionFinder\Criteria;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\SubmissionFinder\Criteria
 */
class CriteriaTest extends TestCase {
    public function testDefaults(): void {
        $criteria = new Criteria(Submission::SORT_HOT);

        $this->assertSame(Submission::SORT_HOT, $criteria->getSortBy());
        $this->assertSame(Criteria::VIEW_ALL, $criteria->getView());
        $this->assertSame(0, $criteria->getExclusions());
        $this->assertFalse($criteria->getStickiesFirst());
        $this->assertSame(25, $criteria->getMaxPerPage());
    }

    /**
     * @doesNotPerformAssertions
     * @dataProvider provideSortModes
     */
    public function testAcceptsValidSortModes(?string $sortMode): void {
        new Criteria($sortMode);
    }

    public function testExcludeHiddenForums(): void {
        $criteria = $this->createCriteria();
        $criteria->excludeHiddenForums();

        $this->assertSame(
            Criteria::EXCLUDE_HIDDEN_FORUMS,
            $criteria->getExclusions() & Criteria::EXCLUDE_HIDDEN_FORUMS
        );
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

        $this->assertSame(Criteria::VIEW_FEATURED, $criteria->getView());
    }

    public function testForumView(): void {
        $forum1 = new Forum('a', 'a', 'a', 'a');
        $forum2 = new Forum('a', 'a', 'a', 'a');

        $criteria = $this->createCriteria()->showForums($forum1, $forum2);

        $this->assertSame(Criteria::VIEW_FORUMS, $criteria->getView());
        $this->assertSame([$forum1, $forum2], $criteria->getForums());
    }

    public function testMaxPerPage(): void {
        $criteria = $this->createCriteria()->setMaxPerPage(69);

        $this->assertSame(69, $criteria->getMaxPerPage());
    }

    public function testModeratedView(): void {
        $criteria = $this->createCriteria()->showModerated();

        $this->assertSame(Criteria::VIEW_MODERATED, $criteria->getView());
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

        $this->assertSame(Criteria::VIEW_SUBSCRIBED, $criteria->getView());
    }

    public function testUserView(): void {
        $user1 = new User('u', 'p');
        $user2 = new User('u', 'p');

        $criteria = new Criteria(Submission::SORT_HOT);
        $criteria->showUsers($user1, $user2);

        $this->assertSame(Criteria::VIEW_USERS, $criteria->getView());
        $this->assertSame([$user1, $user2], $criteria->getUsers());
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

    public function provideGettersThatCannotBeAccessedBeforeMutatorHasBeenCalled(): iterable {
        yield ['getForums'];
        yield ['getUsers'];
    }

    public function provideSortModes(): iterable {
        yield from array_map(function ($mode) {
            return [$mode];
        }, Submission::SORT_OPTIONS);
        yield [null];
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
        return new Criteria(Submission::SORT_HOT);
    }
}
