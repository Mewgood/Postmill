<?php

namespace App\Serializer;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

final class EntityRetrievingDenormalizer implements DenormalizerInterface {
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager) {
        $this->entityManager = $entityManager;
    }

    /**
     * @return object|null
     */
    public function denormalize($data, $type, $format = null, array $context = []) {
        return $this->entityManager->find($type, $data);
    }

    public function supportsDenormalization($data, $type, $format = null): bool {
        return is_scalar($data) && preg_match('/^App\\\\Entity\\\\\w+$/', $type);
    }
}
