<?php

namespace App\Message\Handler;

use App\Flysystem\ImageManager;
use App\Message\DeleteImage;
use App\Repository\ImageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

final class DeleteImageHandler implements MessageHandlerInterface {
    private const LIIP_FILTERS = [
        'submission_thumbnail_1x',
        'submission_thumbnail_2x',
    ];

    /**
     * @var CacheManager
     */
    private $cacheManager;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var MessageBusInterface
     */
    private $messageBus;

    /**
     * @var ImageManager
     */
    private $imageManager;

    /**
     * @var ImageRepository
     */
    private $images;

    /**
     * @var int
     */
    private $batchSize;

    public function __construct(
        CacheManager $cacheManager,
        EntityManagerInterface $entityManager,
        MessageBusInterface $messageBus,
        ImageManager $imageManager,
        ImageRepository $images,
        int $batchSize
    ) {
        $this->cacheManager = $cacheManager;
        $this->entityManager = $entityManager;
        $this->messageBus = $messageBus;
        $this->imageManager = $imageManager;
        $this->images = $images;
        $this->batchSize = $batchSize;
    }

    public function __invoke(DeleteImage $message): void {
        $images = \array_slice($message->getFileNames(), 0, $this->batchSize);
        $images = $this->images->findByFileName($images);
        $images = $this->images->filterOrphanedImages($images);

        foreach ($images as $image) {
            $this->imageManager->prune($image->getFileName());
            $this->entityManager->remove($image);
        }

        $this->cacheManager->remove($images, self::LIIP_FILTERS);
        $this->entityManager->flush();

        $remaining = \array_slice($images, $this->batchSize);

        if ($remaining) {
            $message = new DeleteImage(...$remaining);

            $this->messageBus->dispatch($message);
        }
    }
}
