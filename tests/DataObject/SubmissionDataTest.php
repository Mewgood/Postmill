<?php

namespace App\Tests\DataObject;

use App\DataObject\SubmissionData;
use App\Entity\Forum;
use App\Entity\Image;
use App\Entity\Submission;
use App\Entity\User;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\PhpUnit\ClockMock;

/**
 * @group time-sensitive
 */
class SubmissionDataTest extends TestCase {
    public static function setUpBeforeClass(): void {
        ClockMock::register(SubmissionData::class);
    }

    public function testCannotCreateSubmissionWithMediaTypeUrlAndImage(): void {
        /** @var Forum|\PHPUnit\Framework\MockObject\MockObject $forum */
        $forum = $this->createMock(Forum::class);

        /** @var User|\PHPUnit\Framework\MockObject\MockObject $user */
        $user = $this->createMock(User::class);

        $data = new SubmissionData();
        $data->setForum($forum);
        $data->setTitle('wah');
        $data->setImage(new Image('foof.jpeg', null, null));
        $data->setUrl('http://www.garfield.com');
        $submission = $data->toSubmission($user, null);

        $this->assertNotEmpty($submission->getUrl());
        $this->assertNull($submission->getImage());
    }

    public function testCannotCreateSubmissionWithMediaTypeImageAndUrl(): void {
        /** @var Forum|\PHPUnit\Framework\MockObject\MockObject $forum */
        $forum = $this->createMock(Forum::class);

        /** @var User|\PHPUnit\Framework\MockObject\MockObject $user */
        $user = $this->createMock(User::class);

        $data = new SubmissionData();
        $data->setForum($forum);
        $data->setTitle('wah');
        $data->setImage(new Image('foof.jpeg', null, null));
        $data->setUrl('http://www.garfield.com');
        $data->setMediaType(Submission::MEDIA_IMAGE);
        $submission = $data->toSubmission($user, null);

        $this->assertNotEmpty($submission->getImage());
        $this->assertNull($submission->getUrl());
    }

    /**
     * @dataProvider provideMethodsThatUpdateTheEditableAtProperty
     */
    public function testEditedAtAttributeIsUpdated(string $setter): void {
        /** @var User|\PHPUnit\Framework\MockObject\MockObject $user */
        $user = $this->createMock(User::class);

        /** @var Submission|\PHPUnit\Framework\MockObject\MockObject $submission */
        $submission = $this->createMock(Submission::class);

        $submission
            ->expects($this->once())
            ->method($setter)
            ->with($this->equalTo('http://www.example.com'));

        $submission
            ->expects($this->once())
            ->method('setEditedAt')
            ->with($this->equalTo(new \DateTime('@'.time())));

        $data = new SubmissionData($submission);
        $data->$setter('http://www.example.com');
        $data->updateSubmission($submission, $user);
    }

    public function provideMethodsThatUpdateTheEditableAtProperty(): iterable {
        yield ['setTitle'];
        yield ['setUrl'];
        yield ['setBody'];
    }
}
