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
 * @covers \App\DataObject\SubmissionData
 * @group time-sensitive
 */
class SubmissionDataTest extends TestCase {
    public static function setUpBeforeClass(): void {
        ClockMock::register(SubmissionData::class);
    }

    public function testCannotCreateSubmissionWithMediaTypeUrlAndImage(): void {
        $forum = new Forum('a', 'a', 'a', 'a');
        $user = new User('u', 'p');

        $data = new SubmissionData();
        $data->setForum($forum);
        $data->setTitle('wah');
        $data->setImage(new Image('foof.jpeg', random_bytes(32), null, null));
        $data->setUrl('http://www.garfield.com');
        $submission = $data->toSubmission($user, null);

        $this->assertNotEmpty($submission->getUrl());
        $this->assertNull($submission->getImage());
    }

    public function testCannotCreateSubmissionWithMediaTypeImageAndUrl(): void {
        $forum = new Forum('a', 'a', 'a', 'a');
        $user = new User('u', 'p');

        $data = new SubmissionData();
        $data->setForum($forum);
        $data->setTitle('wah');
        $data->setImage(new Image('foof.jpeg', random_bytes(32), null, null));
        $data->setUrl('http://www.garfield.com');
        $data->setMediaType(Submission::MEDIA_IMAGE);
        $submission = $data->toSubmission($user, null);

        $this->assertNotEmpty($submission->getImage());
        $this->assertNull($submission->getUrl());
    }

    /**
     * @dataProvider provideMethodsThatUpdateTheEditableAtProperty
     */
    public function testEditedAtAttributeIsUpdated(string $getter, string $setter): void {
        $forum = new Forum('a', 'a', 'a', 'a');
        $user = new User('u', 'p');
        $submission = new Submission('title', null, null, $forum, $user, null);

        $data = new SubmissionData($submission);
        $data->$setter('http://www.example.com');
        $data->updateSubmission($submission, $user);

        $this->assertSame('http://www.example.com', $submission->{$getter}());
        $this->assertSame(time(), $submission->getEditedAt()->getTimestamp());
    }

    public function provideMethodsThatUpdateTheEditableAtProperty(): iterable {
        yield ['getTitle', 'setTitle'];
        yield ['getUrl', 'setUrl'];
        yield ['getBody', 'setBody'];
    }
}
