<?php

namespace App\Serializer;

use App\Markdown\MarkdownConverter;
use App\Serializer\Contracts\NormalizeMarkdownInterface;
use Symfony\Component\Serializer\Normalizer\ContextAwareNormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;

class MarkdownNormalizer implements ContextAwareNormalizerInterface, NormalizerAwareInterface {
    use NormalizerAwareTrait;

    public const NORMALIZED_MARKER = 'markdown_normalized';

    /**
     * @var MarkdownConverter
     */
    private $converter;

    public function __construct(MarkdownConverter $converter) {
        $this->converter = $converter;
    }

    public function normalize($object, $format = null, array $context = []): array {
        \assert($object instanceof NormalizeMarkdownInterface);

        $context[self::NORMALIZED_MARKER] = true;
        $data = $this->normalizer->normalize($object, $format, $context);

        foreach ($object->getMarkdownFields() as $rawKey => $renderedKey) {
            if (\is_int($rawKey)) {
                $rawKey = $renderedKey;
                $renderedKey = 'rendered'.ucfirst($renderedKey);
            }

            if (\array_key_exists($rawKey, $data)) {
                if (isset($data[$rawKey])) {
                    $data[$renderedKey] = $this->converter->convertToHtmlCached($data[$rawKey]);
                } else {
                    $data[$renderedKey] = null;
                }
            }
        }

        return $data;
    }

    public function supportsNormalization($data, $format = null, array $context = []): bool {
        return $data instanceof NormalizeMarkdownInterface && empty($context[self::NORMALIZED_MARKER]);
    }
}
