<?php

namespace App\Repository;

use App\Entity\MessageThread;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Pagerfanta\Pagerfanta;

class MessageThreadRepository extends ServiceEntityRepository {
    public function __construct(ManagerRegistry $registry) {
        parent::__construct($registry, MessageThread::class);
    }

    /**
     * @return MessageThread[]|Pagerfanta
     */
    public function findUserMessages(User $user, int $page = 1): Pagerfanta {
        $qb = $this->createQueryBuilder('mt')
            ->where(':user MEMBER OF mt.participants')
            ->orderBy('mt.id', 'DESC')
            ->setParameter(':user', $user);

        $pager = new Pagerfanta(new DoctrineORMAdapter($qb));
        $pager->setMaxPerPage(25);
        $pager->setCurrentPage($page);

        return $pager;
    }
}
