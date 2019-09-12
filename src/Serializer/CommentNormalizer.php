<?php

namespace App\Serializer;

use App\DataObject\CommentData;
use App\Entity\Comment;
use Symfony\Component\Serializer\Normalizer\ContextAwareNormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;

class CommentNormalizer implements ContextAwareNormalizerInterface, NormalizerAwareInterface{
    use NormalizerAwareTrait;

    public const NORMALIZED_MARKER = 'comment_normalized';

    public function supportsNormalization($data, $format = null, array $context = []) {
        return ($data instanceof Comment || $data instanceof CommentData) &&
            empty($context[self::NORMALIZED_MARKER]);
    }


    public function normalize($object, $format = null, array $context = []): array {
        if ($object instanceof Comment) {
            $object = new CommentData($object);
        }

        $context[self::NORMALIZED_MARKER] = true;

        return $this->normalizer->normalize($object, $format, $context);
    }
}
