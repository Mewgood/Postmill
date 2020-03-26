<?php

namespace App\Pagination\QueryReader;

use App\Pagination\PageInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class QueryReader implements QueryReaderInterface {
    /**
     * @var DenormalizerInterface
     */
    private $denormalizer;

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var ValidatorInterface
     */
    private $validator;

    public function __construct(
        DenormalizerInterface $denormalizer,
        RequestStack $requestStack,
        ValidatorInterface $validator
    ) {
        $this->denormalizer = $denormalizer;
        $this->requestStack = $requestStack;
        $this->validator = $validator;
    }

    public function getFromRequest(string $pageDataClass, string $group): ?PageInterface {
        $request = $this->requestStack->getCurrentRequest();

        if ($request && $request->query->has('next')) {
            $groups = [$group];
            $next = $request->query->get('next');

            $page = $this->denormalizer->denormalize($next, $pageDataClass, null, [
                'groups' => $groups,
            ]);
            \assert($page instanceof PageInterface);

            if (\count($this->validator->validate($page, null, $groups)) === 0) {
                return $page;
            }
        }

        return null;
    }
}
