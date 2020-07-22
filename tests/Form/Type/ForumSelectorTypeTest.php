<?php

namespace App\Tests\Form\Type;

use App\Entity\Forum;
use App\Entity\User;
use App\Form\Type\ForumSelectorType;
use App\Repository\ForumRepository;
use Symfony\Component\Form\ChoiceList\View\ChoiceView;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Security\Core\Security;

/**
 * @covers \App\Form\Type\ForumSelectorType
 */
class ForumSelectorTypeTest extends TypeTestCase {
    private $forumIdSequence = 0;

    /**
     * @var ForumRepository|\PHPUnit\Framework\MockObject\MockObject
     */
    private $forums;

    /**
     * @var Security|\PHPUnit\Framework\MockObject\MockObject
     */
    private $security;

    /**
     * @var Forum[]
     */
    private $subscribedForums = [];

    protected function setUp(): void {
        $this->forums = $this->createMock(ForumRepository::class);
        $this->forums
            ->method('findSubscribedForumNames')
            ->willReturnCallback(function () {
                return $this->subscribedForums;
            });
        $this->security = $this->createMock(Security::class);

        parent::setUp();
    }

    protected function getExtensions() {
        return [
            new PreloadedExtension([
                new ForumSelectorType($this->forums, $this->security),
            ], []),
        ];
    }

    public function testListingAsLoggedInUserWithSubscriptions(): void {
        $user = new User('u', 'p');
        $this->security
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        $this->populateForumList($user);

        $view = $this->factory->create(ForumSelectorType::class)->createView();

        $this->assertCount(6, $view->vars['choices']);

        $this->assertSame([
            "alsoFeaturedAndSubscribed \u{2B50} \u{2764}\u{FE0F}",
            "featuredAndSubscribed \u{2B50} \u{2764}\u{FE0F}",
            "subscribed \u{2764}\u{FE0F}",
            "featured \u{2B50}",
            'also_regular',
            'regular',
        ], array_map(static function (ChoiceView $view) {
            return $view->label;
        }, $view->vars['choices']));

        $this->assertSame([true, true, true, false, false, false],
            array_map(static function (ChoiceView $view) {
                return $view->attr['data-subscribed'];
            }, $view->vars['choices'])
        );

        $this->assertSame([true, true, false, true, false, false],
            array_map(static function (ChoiceView $view) {
                return $view->attr['data-featured'];
            }, $view->vars['choices'])
        );
    }

    public function testSubmittingAsLoggedOutUser(): void {
        $this->populateForumList();

        $form = $this->factory->create(ForumSelectorType::class);
        $form->submit('4');

        $this->assertEmpty($form->getErrors());
        $this->assertSame('featuredAndSubscribed', $form->getData()->getName());
    }

    public function testSubmittingInvalidForumId(): void {
        $this->populateForumList();

        $form = $this->factory->create(ForumSelectorType::class);
        $form->submit('7');

        $this->assertCount(1, $form->getErrors());
    }

    private function populateForumList(User $user = null): void {
        $this->forums
            ->expects($this->once())
            ->method('findAll')
            ->willReturn([
                $this->createForum('regular', false, null),
                $this->createForum('also_regular', false, null),
                $this->createForum('featured', true, null),
                $this->createForum('featuredAndSubscribed', true, $user),
                $this->createForum('alsoFeaturedAndSubscribed', true, $user),
                $this->createForum('subscribed', false, $user),
            ]);
    }

    private function createForum($name, bool $featured, ?User $subscriber): Forum {
        $forum = new Forum($name, 'a', 'a', 'a');
        $forum->setFeatured($featured);

        $r = (new \ReflectionObject($forum))->getProperty('id');
        $r->setAccessible(true);
        $r->setValue($forum, ++$this->forumIdSequence);
        $r->setAccessible(false);

        if ($subscriber) {
            $forum->subscribe($subscriber);
            $this->subscribedForums[$forum->getId()] = $forum;
        }

        return $forum;
    }
}
