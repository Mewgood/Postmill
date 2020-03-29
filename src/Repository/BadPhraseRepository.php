<?php

namespace App\Repository;

use App\Entity\BadPhrase;
use App\Entity\Page\TimestampPage;
use App\Pagination\Adapter\DoctrineAdapter;
use App\Pagination\Pager;
use App\Pagination\PaginatorInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class BadPhraseRepository extends ServiceEntityRepository {
    /**
     * @var PaginatorInterface
     */
    private $paginator;

    public function __construct(
        ManagerRegistry $registry,
        PaginatorInterface $paginator
    ) {
        parent::__construct($registry, BadPhrase::class);
        $this->paginator = $paginator;
    }

    public function findPaginated(): Pager {
        $adapter = new DoctrineAdapter($this->createQueryBuilder('bp'));

        return $this->paginator->paginate($adapter, 50, TimestampPage::class);
    }
}
