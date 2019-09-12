<?php

namespace App\Serializer;

use App\DataObject\SubmissionData;
use App\Entity\Submission;
use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use Symfony\Component\Serializer\Normalizer\ContextAwareNormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;

class SubmissionNormalizer implements ContextAwareNormalizerInterface, NormalizerAwareInterface {
    use NormalizerAwareTrait;

    public const NORMALIZED_MARKER = 'submission_normalized';

    /**
     * @var CacheManager
     */
    private $cacheManager;

    public function __construct(CacheManager $cacheManager) {
        $this->cacheManager = $cacheManager;
    }

    public function normalize($object, $format = null, array $context = []): array {
        if ($object instanceof Submission) {
            $object = new SubmissionData($object);
        }

        $context[self::NORMALIZED_MARKER] = true;
        $data = $this->normalizer->normalize($object, $format, $context);

        if (\in_array('submission:read', $context['groups'] ?? [], true)) {
            $image = $object->getImage();

            foreach (['1x', '2x'] as $size) {
                if ($image) {
                    $url = $this->cacheManager->generateUrl(
                        $image,
                        "submission_thumbnail_{$size}"
                    );
                }

                $data["thumbnail_{$size}"] = $url ?? null;
            }
        }

        return $data;
    }

    public function supportsNormalization($data, $format = null, array $context = []): bool {
        return $data instanceof Submission ||
            ($data instanceof SubmissionData && empty($context[self::NORMALIZED_MARKER]));
    }
}
