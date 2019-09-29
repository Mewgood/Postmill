<?php

namespace App\Serializer;

use App\DataObject\SubmissionData;
use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use Symfony\Component\Serializer\Normalizer\ContextAwareNormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;

final class SubmissionDataNormalizer implements ContextAwareNormalizerInterface, NormalizerAwareInterface {
    use NormalizerAwareTrait;

    public const NORMALIZED_MARKER = 'submission_data_normalized';

    /**
     * @var CacheManager
     */
    private $cacheManager;

    public function __construct(CacheManager $cacheManager) {
        $this->cacheManager = $cacheManager;
    }

    public function normalize($object, $format = null, array $context = []) {
        $context[self::NORMALIZED_MARKER][spl_object_id($object)] = true;
        $data = $this->normalizer->normalize($object, $format, $context);

        if (isset($data['image'])) {
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
        return $data instanceof SubmissionData &&
            empty($context[self::NORMALIZED_MARKER][spl_object_id($data)]);
    }
}
