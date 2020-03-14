<?php

namespace App\Pagination;

use App\Pagination\Adapter\AdapterInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class Paginator {
    /**
     * @var NormalizerInterface|DenormalizerInterface
     */
    private $normalizer;

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var ValidatorInterface
     */
    private $validator;

    public function __construct(
        NormalizerInterface $normalizer,
        RequestStack $requestStack,
        ValidatorInterface $validator
    ) {
        if (!$normalizer instanceof DenormalizerInterface) {
            throw new \InvalidArgumentException(
                '$normalizer must implement '.
                DenormalizerInterface::class
            );
        }

        $this->normalizer = $normalizer;
        $this->requestStack = $requestStack;
        $this->validator = $validator;
    }

    public function paginate(
        AdapterInterface $adapter,
        int $maxPerPage,
        string $pageDataClass,
        string $group = 'pager'
    ): Pager {
        $page = $this->getPage($pageDataClass, $group) ?? new $pageDataClass();

        $result = $adapter->getResults($maxPerPage, $group, $page);
        $pagerEntity = $result->getPagerEntity();

        if ($pagerEntity) {
            $page->populateFromPagerEntity($pagerEntity);

            $nextPageParams = $this->normalizer->normalize($page, null, [
                'groups' => [$group],
            ]);
        }

        return new Pager($result->getEntries(), $nextPageParams ?? []);
    }

    /**
     * Get the page data, if any, from the current request.
     */
    public function getPage(string $pageDataClass, string $group): ?PageInterface {
        $request = $this->requestStack->getCurrentRequest();

        if ($request && $request->query->has('next')) {
            $groups = (array) $group;
            $next = $request->query->get('next');

            $page = $this->normalizer->denormalize($next, $pageDataClass, null, [
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
