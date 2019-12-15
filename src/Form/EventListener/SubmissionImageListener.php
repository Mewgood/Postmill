<?php

namespace App\Form\EventListener;

use App\DataObject\SubmissionData;
use App\Entity\Submission;
use App\Repository\ImageRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\Event\PostSubmitEvent;
use Symfony\Component\Form\FormEvents;

/**
 * Handle submission image uploads.
 */
final class SubmissionImageListener implements EventSubscriberInterface {
    /**
     * @var ImageRepository
     */
    private $images;

    public function __construct(ImageRepository $images) {
        $this->images = $images;
    }

    public static function getSubscribedEvents(): array {
        return [
            FormEvents::POST_SUBMIT => ['onPostSubmit', -200],
        ];
    }

    public function onPostSubmit(PostSubmitEvent $event): void {
        if (!$event->getForm()->isValid()) {
            return;
        }

        $data = $event->getData();

        \assert($data instanceof SubmissionData);

        $upload = $data->getUploadedImage();

        if ($upload && !$data->getImage() && $data->getMediaType() === Submission::MEDIA_IMAGE) {
            $data->setImage($this->images->findOrCreateFromUpload($upload));
        }
    }
}
