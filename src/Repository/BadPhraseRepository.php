<?php

namespace App\Repository;

use App\Entity\BadPhrase;
use App\Pagination\TimestampPage;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use PagerWave\Adapter\DoctrineAdapter;
use PagerWave\CursorInterface;
use PagerWave\PaginatorInterface;

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

    public function findPaginated(): CursorInterface {
        $adapter = new DoctrineAdapter($this->createQueryBuilder('bp'));

        return $this->paginator->paginate($adapter, 50, new TimestampPage());
    }
}
