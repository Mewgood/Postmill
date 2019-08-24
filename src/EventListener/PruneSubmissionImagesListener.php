<?php

namespace App\EventListener;

use App\Entity\Submission;
use App\Event\DeleteSubmissionEvent;
use App\Event\EditSubmissionEvent;
use App\Message\DeleteSubmissionImage;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\MessageBusInterface;

class PruneSubmissionImagesListener implements EventSubscriberInterface {
    /**
     * @var MessageBusInterface
     */
    private $messageBus;

    public static function getSubscribedEvents(): array {
        return [
            DeleteSubmissionEvent::class => ['onDeleteSubmission'],
            EditSubmissionEvent::class => ['onEditSubmission'],
        ];
    }

    public function __construct(MessageBusInterface $messageBus) {
        $this->messageBus = $messageBus;
    }

    public function onDeleteSubmission(DeleteSubmissionEvent $event): void {
        $images = array_filter(array_map(function (Submission $submission) {
            return $submission->getImage();
        }, $event->getSubmissions()), 'is_string');

        if ($images) {
            $message = new DeleteSubmissionImage(...$images);

            $this->messageBus->dispatch($message);
        }
    }

    public function onEditSubmission(EditSubmissionEvent $event): void {
        $before = $event->getBefore()->getImage();
        $after = $event->getAfter()->getImage();

        if ($before && $before !== $after) {
            $message = new DeleteSubmissionImage($before);

            $this->messageBus->dispatch($message);
        }
    }
}
