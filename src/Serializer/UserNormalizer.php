<?php

namespace App\Serializer;

use App\DataObject\UserData;
use App\Entity\User;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class UserNormalizer implements NormalizerInterface, NormalizerAwareInterface {
    use NormalizerAwareTrait;

    public function normalize($object, $format = null, array $context = []): array {
        $object = new UserData($object);

        return $this->normalizer->normalize($object, $format, $context);
    }

    public function supportsNormalization($data, $format = null): bool {
        return $data instanceof User;
    }
}
