<?php

namespace App\Form\EventListener;

use App\Entity\Submission;
use App\Flysystem\SubmissionImageManager;
use App\Form\Model\SubmissionData;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\Event\PostSubmitEvent;
use Symfony\Component\Form\FormEvents;

/**
 * Handle submission image uploads.
 */
final class SubmissionImageListener implements EventSubscriberInterface {
    /**
     * @var SubmissionImageManager
     */
    private $imageHelper;

    public function __construct(SubmissionImageManager $imageHelper) {
        $this->imageHelper = $imageHelper;
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

        if (
            $upload === null ||
            $data->getImage() !== null ||
            $data->getMediaType() !== Submission::MEDIA_IMAGE
        ) {
            return;
        }

        $source = $upload->getPathname();
        $filename = $this->imageHelper->getFileName($source);

        $this->imageHelper->store($source, $filename);

        $data->setImage($filename);
    }
}
