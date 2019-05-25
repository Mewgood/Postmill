<?php

namespace App\Pagination;

use App\Pagination\Adapter\AdapterInterface;
use App\Pagination\Form\PageType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class Paginator {
    /**
     * @var FormFactoryInterface
     */
    private $formFactory;

    /**
     * @var NormalizerInterface
     */
    private $normalizer;

    /**
     * @var RequestStack
     */
    private $requestStack;

    public function __construct(
        FormFactoryInterface $formFactory,
        NormalizerInterface $normalizer,
        RequestStack $requestStack
    ) {
        $this->normalizer = $normalizer;
        $this->formFactory = $formFactory;
        $this->requestStack = $requestStack;
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

            $nextPageParams = $this->normalizer->normalize($page, null, [$group]);
        }

        return new Pager($result->getEntries(), $nextPageParams ?? []);
    }

    /**
     * Get the page data, if any, from the current request.
     *
     * @param string $pageDataClass
     * @param string $group
     *
     * @return PageInterface|null
     */
    public function getPage(string $pageDataClass, string $group): ?PageInterface {
        $page = new $pageDataClass();
        $request = $this->requestStack->getCurrentRequest();

        if ($request) {
            $form = $this->formFactory->createNamed('next', PageType::class, $page, [
                'group' => $group,
            ]);

            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                return $page;
            }
        }

        return null;
    }
}
