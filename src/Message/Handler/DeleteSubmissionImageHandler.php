<?php

namespace App\Message\Handler;

use App\Flysystem\ImageManager;
use App\Message\DeleteSubmissionImage;
use App\Repository\SubmissionRepository;
use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

final class DeleteSubmissionImageHandler implements MessageHandlerInterface {
    private const LIIP_FILTERS = [
        'submission_thumbnail_1x',
        'submission_thumbnail_2x',
    ];

    /**
     * @var CacheManager
     */
    private $cacheManager;

    /**
     * @var MessageBusInterface
     */
    private $messageBus;

    /**
     * @var ImageManager
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
        CacheManager $cacheManager,
        MessageBusInterface $messageBus,
        ImageManager $imageManager,
        SubmissionRepository $submissions,
        int $batchSize
    ) {
        $this->cacheManager = $cacheManager;
        $this->messageBus = $messageBus;
        $this->imageManager = $imageManager;
        $this->submissions = $submissions;
        $this->batchSize = $batchSize;
    }

    public function __invoke(DeleteSubmissionImage $message): void {
        $images = $message->getImages();
        $batch = \array_slice($images, 0, $this->batchSize);

        $removableImages = $this->submissions->findRemovableImages($batch);

        $this->cacheManager->remove($removableImages, self::LIIP_FILTERS);

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
