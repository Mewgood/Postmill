<?php

namespace App\Message\Handler;

use App\Flysystem\SubmissionImageManager;
use App\Message\DeleteSubmissionImage;
use App\Repository\SubmissionRepository;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

final class DeleteSubmissionImageHandler implements MessageHandlerInterface {
    /**
     * @var MessageBusInterface
     */
    private $messageBus;

    /**
     * @var SubmissionImageManager
     */
    private $imageManager;

    /**
     * @var SubmissionRepository
     */
    private $submissions;

    /**
     * @var int
     */
    private $batchSize;

    public function __construct(
        MessageBusInterface $messageBus,
        SubmissionImageManager $imageManager,
        SubmissionRepository $submissions,
        int $batchSize
    ) {
        $this->messageBus = $messageBus;
        $this->imageManager = $imageManager;
        $this->submissions = $submissions;
        $this->batchSize = $batchSize;
    }

    public function __invoke(DeleteSubmissionImage $message): void {
        $images = $message->getImages();
        $batch = \array_slice($images, 0, $this->batchSize);

        $removableImages = $this->submissions->findRemovableImages($batch);

        foreach ($removableImages as $image) {
            $this->imageManager->prune($image);
        }

        $rest = \array_slice($images, $this->batchSize);

        if ($rest) {
            $message = new DeleteSubmissionImage(...$rest);

            $this->messageBus->dispatch($message);
        }
    }
}
