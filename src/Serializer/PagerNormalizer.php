<?php

namespace App\Serializer;

use App\Pagination\Pager;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class PagerNormalizer implements NormalizerInterface, NormalizerAwareInterface {
    use NormalizerAwareTrait;

    public function normalize($object, $format = null, array $context = []): array {
        \assert($object instanceof Pager);

        $entries = \iterator_to_array($object);

        return array_filter([
            'entries' => $this->normalizer->normalize($entries, $format, $context),
            'nextPage' => $object->hasNextPage()
                ? http_build_query($object->getNextPageParams())
                : null,
        ]);
    }

    public function supportsNormalization($data, $format = null): bool {
        return $data instanceof Pager;
    }
}
