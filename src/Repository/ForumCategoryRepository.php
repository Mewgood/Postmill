<?php

namespace App\Repository;

use App\Entity\ForumCategory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Pagerfanta\Pagerfanta;

class ForumCategoryRepository extends ServiceEntityRepository {
    public function __construct(ManagerRegistry $registry) {
        parent::__construct($registry, ForumCategory::class);
    }

    /**
     * @return Pagerfanta|ForumCategory[]
     */
    public function findPaginated(int $page, int $maxPerPage = 25): Pagerfanta {
        $qb = $this->createQueryBuilder('fc')
            ->orderBy('fc.normalizedName', 'ASC');

        $pager = new Pagerfanta(new DoctrineORMAdapter($qb, false, false));
        $pager->setMaxPerPage($maxPerPage);
        $pager->setCurrentPage($page);

        return $pager;
    }
}
