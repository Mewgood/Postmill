<?php

namespace App\Repository;

use App\Entity\Contracts\VisibilityInterface;
use App\Entity\Forum;
use App\Entity\Image;
use App\Entity\Submission;
use App\Flysystem\ImageManager;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * @method Image|null find($id, $lockMode = null, $lockVersion = null)
 * @method Image|null findOneBy(array $criteria, array $orderBy = null)
 * @method Image[]    findAll()
 * @method Image[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 * @method Image[]    findByFileName(string|string[] $fileNames)
 */
class ImageRepository extends ServiceEntityRepository {
    /**
     * @var ImageManager
     */
    private $imageManager;

    public function __construct(ManagerRegistry $registry, ImageManager $imageManager) {
        parent::__construct($registry, Image::class);

        $this->imageManager = $imageManager;
    }

    public function findOrCreateFromPath(string $source): Image {
        $filename = $this->imageManager->getFileName($source);
        $image = $this->findOneBy(['fileName' => $filename]);

        if (!$image) {
            [$width, $height] = @getimagesize($source);
            $image = new Image($filename, $width, $height);
        } elseif (!$image->getWidth() || !$image->getHeight()) {
            [$width, $height] = @getimagesize($source);
            $image->setDimensions($width, $height);
        }

        $this->imageManager->store($source, $filename);

        return $image;
    }

    public function findOrCreateFromUpload(UploadedFile $upload): Image {
        return $this->findOrCreateFromPath($upload->getPathname());
    }

    /**
     * @param Image[] $images
     *
     * @return Image[]|array
     */
    public function filterOrphanedImages(array $images): array {
        return $this->createQueryBuilder('i')
            ->andWhere('i IN (?1)')
            ->andWhere('i NOT IN (SELECT IDENTITY(f1.lightBackgroundImage) FROM '.Forum::class.' f1 WHERE f1.lightBackgroundImage IS NOT NULL)')
            ->andWhere('i NOT IN (SELECT IDENTITY(f2.darkBackgroundImage) FROM '.Forum::class.' f2 WHERE f2.darkBackgroundImage IS NOT NULL)')
            ->andWhere('i NOT IN (SELECT IDENTITY(s.image) FROM '.Submission::class.' s WHERE s.image IS NOT NULL AND s.visibility <> ?2)')
            ->setParameter(1, $images)
            ->setParameter(2, VisibilityInterface::VISIBILITY_SOFT_DELETED)
            ->getQuery()
            ->execute();
    }
}
