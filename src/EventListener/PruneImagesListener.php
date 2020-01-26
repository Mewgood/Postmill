<?php

namespace App\EventListener;

use App\Entity\Image;
use App\Event\ForumDeleted;
use App\Event\ForumUpdated;
use App\Event\SubmissionDeleted;
use App\Event\SubmissionUpdated;
use App\Message\DeleteImage;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\MessageBusInterface;

class PruneImagesListener implements EventSubscriberInterface {
    /**
     * @var MessageBusInterface
     */
    private $messageBus;

    public static function getSubscribedEvents(): array {
        return [
            ForumDeleted::class => ['onDeleteForum'],
            SubmissionDeleted::class => ['onDeleteSubmission'],
            ForumUpdated::class => ['onEditForum'],
            SubmissionUpdated::class => ['onEditSubmission'],
        ];
    }

    public function __construct(MessageBusInterface $messageBus) {
        $this->messageBus = $messageBus;
    }

    public function onDeleteSubmission(SubmissionDeleted $event): void {
        $images = [];

        foreach ($event->getSubmissions() as $submission) {
            $image = $submission->getImage();

            if ($image) {
                $images[] = $image->getId();
            }
        }

        if ($images) {
            $this->messageBus->dispatch(new DeleteImage(...$images));
        }
    }

    public function onEditSubmission(SubmissionUpdated $event): void {
        $before = $event->getBefore()->getImage();
        $after = $event->getAfter()->getImage();

        if ($before && $before !== $after) {
            $message = new DeleteImage($before->getFileName());

            $this->messageBus->dispatch($message);
        }
    }

    public function onDeleteForum(ForumDeleted $event): void {
        $images = array_map(function (Image $image) {
            return $image->getFileName();
        }, array_filter([
            $event->getForum()->getLightBackgroundImage(),
            $event->getForum()->getDarkBackgroundImage(),
        ]));

        if ($images) {
            $this->messageBus->dispatch(new DeleteImage(...$images));
        }
    }

    public function onEditForum(ForumUpdated $event): void {
        $images = [];

        $before = $event->getBefore()->getLightBackgroundImage();
        $after = $event->getAfter()->getLightBackgroundImage();

        if ($before && $before !== $after) {
            $images[] = $before->getFileName();
        }

        $before = $event->getBefore()->getDarkBackgroundImage();
        $after = $event->getAfter()->getDarkBackgroundImage();

        if ($before && $before !== $after) {
            $images[] = $before->getFileName();
        }

        if ($images) {
            $this->messageBus->dispatch(new DeleteImage(...$images));
        }
    }
}
